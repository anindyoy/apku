<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransaksiService
{
    public function buatSaldoAwal(
        User $user,
        BukuKas $bukuKas,
        SumberDana $sumberDana,
        int $nominal,
        string $deskripsi = 'Saldo awal',
    ): Transaksi {
        if ($nominal < 0) {
            throw ValidationException::withMessages(['nominal' => 'Saldo awal tidak boleh negatif.']);
        }

        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $sumberDana);

        return DB::transaction(function () use ($user, $bukuKas, $sumberDana, $nominal, $deskripsi): Transaksi {
            BukuKas::withoutGlobalScopes()->whereKey($bukuKas->id)->lockForUpdate()->firstOrFail();
            SumberDana::withoutGlobalScopes()->whereKey($sumberDana->id)->lockForUpdate()->firstOrFail();
            $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
                'user_id' => $user->id,
                'buku_kas_id' => $bukuKas->id,
                'sumber_dana_id' => $sumberDana->id,
                'tanggal' => now(),
                'nominal' => $nominal,
                'jenis' => 'Pemasukan',
                'deskripsi' => $deskripsi,
            ]));
            $this->terapkanDampak($transaksi);

            return $transaksi;
        });
    }

    public function buat(User $user, array $data, string $jenis): Transaksi
    {
        if (! in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
            throw ValidationException::withMessages(['jenis' => 'Jenis transaksi tidak valid.']);
        }

        if ((int) ($data['nominal'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transaksi harus lebih dari nol.']);
        }

        // Normalisasi kunci lama dompet_id ke sumber_dana_id untuk kompatibilitas test lama.
        $data['sumber_dana_id'] ??= $data['dompet_id'] ?? null;
        $data['sumber_dana_id_tujuan'] ??= $data['dompet_id_tujuan'] ?? null;

        $bukuKas = BukuKas::withoutGlobalScopes()->findOrFail($data['buku_kas_id']);
        $sumberDana = SumberDana::withoutGlobalScopes()->findOrFail($data['sumber_dana_id']);
        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $sumberDana);
        $this->pastikanKategoriValid($bukuKas, filled($data['kategori_id'] ?? null) ? (int) $data['kategori_id'] : null, $jenis);

        return DB::transaction(function () use ($user, $data, $jenis): Transaksi {
            $bukuKas = BukuKas::withoutGlobalScopes()->whereKey($data['buku_kas_id'])->lockForUpdate()->firstOrFail();
            $sumberDana = SumberDana::withoutGlobalScopes()->whereKey($data['sumber_dana_id'])->lockForUpdate()->firstOrFail();
            $this->pastikanTujuanDapatDikelola($user, $bukuKas, $sumberDana);
            $this->pastikanKategoriValid($bukuKas, filled($data['kategori_id'] ?? null) ? (int) $data['kategori_id'] : null, $jenis);

            $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
                'user_id' => $user->id,
                'import_transaksi_id' => $data['import_transaksi_id'] ?? null,
                'pengaruhi_saldo' => $data['pengaruhi_saldo'] ?? true,
                'buku_kas_id' => $bukuKas->id,
                'sumber_dana_id' => $sumberDana->id,
                'kategori_id' => filled($data['kategori_id'] ?? null) ? (int) $data['kategori_id'] : null,
                'tanggal' => $data['tanggal'] ?? now(),
                'nominal' => (int) $data['nominal'],
                'jenis' => $jenis,
                'deskripsi' => $data['deskripsi'] ?? null,
            ]));
            $this->terapkanDampak($transaksi);

            return $transaksi;
        });
    }

    /** @return array{keluar: Transaksi, masuk: Transaksi} */
    public function transferBukuKas(
        User $user,
        BukuKas $bukuKasAsal,
        BukuKas $bukuKasTujuan,
        SumberDana $sumberDanaAsal,
        SumberDana $sumberDanaTujuan,
        int $nominal,
        mixed $tanggal = null,
        ?string $deskripsi = null,
    ): array {
        if ($nominal <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transfer harus lebih dari nol.']);
        }

        if ($bukuKasAsal->is($bukuKasTujuan)) {
            throw ValidationException::withMessages(['buku_kas_id_tujuan' => 'Kas tujuan harus berbeda dari kas asal.']);
        }

        if (
            $bukuKasAsal->user_id !== $user->id
            || $bukuKasTujuan->user_id !== $user->id
            || $sumberDanaAsal->user_id !== $user->id
            || $sumberDanaTujuan->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPada($bukuKasAsal)
            || ! $user->dapatMengelolaTransaksiPada($bukuKasTujuan)
            || ! $user->dapatMengelolaTransaksiPadaDompet($sumberDanaTujuan)
        ) {
            throw new AuthorizationException('Sumber dana atau kas tidak dapat dikelola.');
        }

        return DB::transaction(function () use ($user, $bukuKasAsal, $bukuKasTujuan, $sumberDanaAsal, $sumberDanaTujuan, $nominal, $tanggal, $deskripsi): array {
            $bukuKas = BukuKas::withoutGlobalScopes()
                ->whereKey([$bukuKasAsal->id, $bukuKasTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $sumberDana = SumberDana::withoutGlobalScopes()
                ->whereKey([$sumberDanaAsal->id, $sumberDanaTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($bukuKas->count() !== 2 || $sumberDana->count() !== ($sumberDanaAsal->is($sumberDanaTujuan) ? 1 : 2)) {
                throw new AuthorizationException('Sumber dana atau kas tidak tersedia.');
            }

            $kodeTransfer = (string) Str::uuid();
            $data = [
                'user_id' => $user->id,
                'tanggal' => $tanggal ?? now(),
                'nominal' => $nominal,
                'transfer_code' => $kodeTransfer,
                'tipe_transfer' => 'buku_kas',
                'deskripsi' => $deskripsi,
            ];
            $transaksi = Transaksi::withoutEvents(fn (): array => [
                'keluar' => Transaksi::create($data + [
                    'buku_kas_id' => $bukuKasAsal->id,
                    'sumber_dana_id' => $sumberDanaAsal->id,
                    'jenis' => 'Transfer Pengeluaran',
                    'tujuan_buku_tabungan_id' => $bukuKasTujuan->id,
                ]),
                'masuk' => Transaksi::create($data + [
                    'buku_kas_id' => $bukuKasTujuan->id,
                    'sumber_dana_id' => $sumberDanaTujuan->id,
                    'jenis' => 'Transfer Pemasukan',
                    'asal_buku_tabungan_id' => $bukuKasAsal->id,
                ]),
            ]);

            $this->terapkanDampak($transaksi['keluar']);
            $this->terapkanDampak($transaksi['masuk']);

            return $transaksi;
        });
    }

    public function ubah(User $user, Transaksi $transaksi, array $data): Transaksi
    {
        // Normalisasi kunci lama dompet_id ke sumber_dana_id untuk kompatibilitas test lama.
        $data['sumber_dana_id'] ??= $data['dompet_id'] ?? null;
        $data['sumber_dana_id_tujuan'] ??= $data['dompet_id_tujuan'] ?? null;
        unset($data['dompet_id'], $data['dompet_id_tujuan']);

        $this->pastikanDapatMengelola($user, $transaksi);

        if ($transaksi->audit_saldo_dompet_detail_id) {
            throw ValidationException::withMessages([
                'transaksi' => 'Transaksi penyesuaian saldo tidak dapat diubah. Lakukan audit saldo baru.',
            ]);
        }

        $proses = function () use ($user, $transaksi, $data): Transaksi {
            $terkunci = Transaksi::withoutGlobalScopes()->whereKey($transaksi->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->transfer_code) {
                return $this->ubahPasanganTransfer($user, $terkunci, $data);
            }

            $dataBaru = array_merge($terkunci->only([
                'buku_kas_id', 'sumber_dana_id', 'kategori_id', 'tanggal', 'nominal', 'jenis', 'deskripsi',
            ]), $data);
            $bukuKasBaru = BukuKas::withoutGlobalScopes()->findOrFail($dataBaru['buku_kas_id']);
            $sumberDanaBaru = SumberDana::withoutGlobalScopes()->findOrFail($dataBaru['sumber_dana_id']);

            $this->pastikanTujuanDapatDikelola($user, $bukuKasBaru, $sumberDanaBaru);
            $dataBaru['kategori_id'] = filled($dataBaru['kategori_id'] ?? null) ? (int) $dataBaru['kategori_id'] : null;

            // Pasangan kas dan kategori divalidasi ulang hanya ketika salah satunya berubah.
            if ((int) $dataBaru['buku_kas_id'] !== (int) $terkunci->buku_kas_id || $dataBaru['kategori_id'] !== ($terkunci->kategori_id === null ? null : (int) $terkunci->kategori_id)) {
                $this->pastikanKategoriValid($bukuKasBaru, $dataBaru['kategori_id'], $dataBaru['jenis']);
            }

            $this->balikDampak($terkunci);
            Transaksi::withoutEvents(fn () => $terkunci->update($dataBaru));
            $this->terapkanDampak($terkunci->fresh());

            return $terkunci->fresh();
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    public function hapus(User $user, Transaksi $transaksi): bool
    {
        $this->pastikanDapatMengelola($user, $transaksi);

        if ($transaksi->audit_saldo_dompet_detail_id) {
            throw ValidationException::withMessages([
                'transaksi' => 'Transaksi penyesuaian saldo tidak dapat dihapus. Lakukan audit saldo baru.',
            ]);
        }

        $proses = function () use ($transaksi): bool {
            $query = $transaksi->transfer_code
                ? Transaksi::withoutGlobalScopes()->where('transfer_code', $transaksi->transfer_code)
                : Transaksi::withoutGlobalScopes()->whereKey($transaksi->id);
            $semua = $query->orderBy('id')->lockForUpdate()->get();

            foreach ($semua as $item) {
                $this->balikDampak($item);
            }

            Transaksi::withoutEvents(function () use ($semua): void {
                foreach ($semua as $item) {
                    $item->delete();
                }
            });

            return true;
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    private function ubahPasanganTransfer(User $user, Transaksi $transaksi, array $data): Transaksi
    {
        $pasangan = Transaksi::withoutGlobalScopes()
            ->where('transfer_code', $transaksi->transfer_code)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($pasangan->count() !== 2) {
            throw new \RuntimeException('Pasangan transaksi transfer tidak lengkap.');
        }

        $keluar = $pasangan->firstWhere('jenis', 'Transfer Pengeluaran');
        $masuk = $pasangan->firstWhere('jenis', 'Transfer Pemasukan');

        if (! $keluar || ! $masuk || $keluar->tipe_transfer !== $masuk->tipe_transfer) {
            throw new \RuntimeException('Pasangan transaksi transfer tidak valid.');
        }

        $tipeTransfer = $keluar->tipe_transfer ?: 'buku_kas';
        $nominal = (int) ($data['nominal'] ?? $keluar->nominal);

        if ($nominal <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transfer harus lebih dari nol.']);
        }

        // Normalisasi kunci lama dompet_id agar pasangan transfer lama tetap dapat diubah.
        $data['sumber_dana_id'] ??= $data['dompet_id'] ?? null;
        $data['sumber_dana_id_tujuan'] ??= $data['dompet_id_tujuan'] ?? null;

        $kasAsalId = (int) ($data['buku_kas_id'] ?? $keluar->buku_kas_id);
        $kasTujuanId = (int) ($tipeTransfer === 'buku_kas'
            ? ($data['buku_kas_id_tujuan'] ?? $masuk->buku_kas_id)
            : ($data['buku_kas_id'] ?? $masuk->buku_kas_id));
        $sumberDanaAsalId = (int) ($data['sumber_dana_id'] ?? $keluar->sumber_dana_id);
        $sumberDanaTujuanId = (int) ($tipeTransfer === 'dompet'
            ? ($data['sumber_dana_id_tujuan'] ?? $masuk->sumber_dana_id)
            : ($data['sumber_dana_id'] ?? $masuk->sumber_dana_id));

        if ($tipeTransfer === 'buku_kas' && $kasAsalId === $kasTujuanId) {
            throw ValidationException::withMessages(['buku_kas_id_tujuan' => 'Kas tujuan harus berbeda dari kas asal.']);
        }

        if ($tipeTransfer === 'dompet' && $sumberDanaAsalId === $sumberDanaTujuanId) {
            throw ValidationException::withMessages(['sumber_dana_id_tujuan' => 'Sumber dana tujuan harus berbeda dari sumber dana asal.']);
        }

        $kas = BukuKas::withoutGlobalScopes()
            ->whereKey(array_unique([$kasAsalId, $kasTujuanId]))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $sumberDana = SumberDana::withoutGlobalScopes()
            ->whereKey(array_unique([$sumberDanaAsalId, $sumberDanaTujuanId]))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ([[$kasAsalId, $sumberDanaAsalId], [$kasTujuanId, $sumberDanaTujuanId]] as [$kasId, $sumberDanaId]) {
            $bukuKas = $kas->get($kasId);
            $sumberDanaTerpilih = $sumberDana->get($sumberDanaId);

            if (! $bukuKas || ! $sumberDanaTerpilih || $bukuKas->user_id !== $user->id) {
                throw new AuthorizationException('Sumber dana atau kas tidak dapat dikelola.');
            }

            $this->pastikanTujuanDapatDikelola($user, $bukuKas, $sumberDanaTerpilih);
        }

        foreach ($pasangan as $item) {
            $this->pastikanDapatMengelola($user, $item);
            $this->balikDampak($item);
        }

        foreach ($pasangan as $item) {
            $pengeluaran = $item->jenis === 'Transfer Pengeluaran';
            Transaksi::withoutEvents(fn () => $item->update([
                'buku_kas_id' => $pengeluaran ? $kasAsalId : $kasTujuanId,
                'sumber_dana_id' => $pengeluaran ? $sumberDanaAsalId : $sumberDanaTujuanId,
                'tujuan_buku_tabungan_id' => $tipeTransfer === 'buku_kas' && $pengeluaran ? $kasTujuanId : null,
                'asal_buku_tabungan_id' => $tipeTransfer === 'buku_kas' && ! $pengeluaran ? $kasAsalId : null,
                'nominal' => $nominal,
                'tanggal' => $data['tanggal'] ?? $item->tanggal,
                'deskripsi' => $data['deskripsi'] ?? $item->deskripsi,
            ]));
            $this->terapkanDampak($item->fresh());
        }

        return Transaksi::withoutGlobalScopes()->findOrFail($transaksi->id);
    }

    private function pastikanDapatMengelola(User $user, Transaksi $transaksi): void
    {
        if ($transaksi->user_id !== $user->id) {
            throw new AuthorizationException('Hanya pembuat transaksi yang dapat mengubah atau menghapus transaksi ini.');
        }

        $bukuKas = BukuKas::withoutGlobalScopes()->findOrFail($transaksi->buku_kas_id);
        $sumberDana = SumberDana::withoutGlobalScopes()->withTrashed()->findOrFail($transaksi->sumber_dana_id);
        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $sumberDana);
    }

    private function pastikanTujuanDapatDikelola(User $user, BukuKas $bukuKas, SumberDana $sumberDana): void
    {
        if (
            $sumberDana->user_id !== $user->id
            || $sumberDana->trashed()
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
            || ! $user->dapatMengelolaTransaksiPadaDompet($sumberDana)
        ) {
            throw new AuthorizationException('Transaksi tidak dapat dikelola.');
        }
    }

    private function pastikanKategoriValid(BukuKas $bukuKas, ?int $kategoriId, string $jenis): void
    {
        // Kategori bersifat opsional; transaksi tanpa kategori tetap sah.
        if ($kategoriId === null) {
            return;
        }

        $kategori = Kategori::withoutGlobalScopes()->find($kategoriId);

        if (! $kategori || ! $kategori->terhubungKe($bukuKas->id)) {
            throw ValidationException::withMessages([
                'kategori_id' => 'Kategori tidak terhubung ke kas yang dipilih.',
            ]);
        }

        if ($kategori->tipe !== $jenis) {
            throw ValidationException::withMessages([
                'kategori_id' => 'Tipe kategori tidak sesuai dengan jenis transaksi.',
            ]);
        }
    }

    private function balikDampak(Transaksi $transaksi): void
    {
        if (! $transaksi->pengaruhi_saldo) {
            return;
        }

        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->decrement('saldo', $dampak);
        SumberDana::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->sumber_dana_id)->decrement('saldo', $dampak);
    }

    private function terapkanDampak(Transaksi $transaksi): void
    {
        if (! $transaksi->pengaruhi_saldo) {
            return;
        }

        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->increment('saldo', $dampak);
        SumberDana::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->sumber_dana_id)->increment('saldo', $dampak);
    }

    private function dampak(Transaksi $transaksi): int
    {
        return in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'], true)
            ? -$transaksi->nominal
            : $transaksi->nominal;
    }
}
