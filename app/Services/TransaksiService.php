<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
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
        Dompet $dompet,
        int $nominal,
        string $deskripsi = 'Saldo awal',
    ): Transaksi {
        if ($nominal < 0) {
            throw ValidationException::withMessages(['nominal' => 'Saldo awal tidak boleh negatif.']);
        }

        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $dompet);

        return DB::transaction(function () use ($user, $bukuKas, $dompet, $nominal, $deskripsi): Transaksi {
            BukuKas::withoutGlobalScopes()->whereKey($bukuKas->id)->lockForUpdate()->firstOrFail();
            Dompet::withoutGlobalScopes()->whereKey($dompet->id)->lockForUpdate()->firstOrFail();
            $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
                'user_id' => $user->id,
                'buku_kas_id' => $bukuKas->id,
                'dompet_id' => $dompet->id,
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

        $bukuKas = BukuKas::withoutGlobalScopes()->findOrFail($data['buku_kas_id']);
        $dompet = Dompet::withoutGlobalScopes()->findOrFail($data['dompet_id']);
        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $dompet);
        $this->pastikanKategoriValid($user, $data['jenis_transaksi_id'] ?? null, $jenis);

        return DB::transaction(function () use ($user, $data, $jenis): Transaksi {
            $bukuKas = BukuKas::withoutGlobalScopes()->whereKey($data['buku_kas_id'])->lockForUpdate()->firstOrFail();
            $dompet = Dompet::withoutGlobalScopes()->whereKey($data['dompet_id'])->lockForUpdate()->firstOrFail();
            $this->pastikanTujuanDapatDikelola($user, $bukuKas, $dompet);
            $this->pastikanKategoriValid($user, $data['jenis_transaksi_id'] ?? null, $jenis);

            $transaksi = Transaksi::withoutEvents(fn () => Transaksi::create([
                'user_id' => $user->id,
                'import_transaksi_id' => $data['import_transaksi_id'] ?? null,
                'buku_kas_id' => $bukuKas->id,
                'dompet_id' => $dompet->id,
                'jenis_transaksi_id' => $data['jenis_transaksi_id'] ?? null,
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
        Dompet $dompetAsal,
        Dompet $dompetTujuan,
        int $nominal,
        mixed $tanggal = null,
        ?string $deskripsi = null,
    ): array {
        if ($nominal <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal transfer harus lebih dari nol.']);
        }

        if ($bukuKasAsal->is($bukuKasTujuan)) {
            throw ValidationException::withMessages(['buku_kas_id_tujuan' => 'Buku kas tujuan harus berbeda dari buku kas asal.']);
        }

        if (
            $bukuKasAsal->user_id !== $user->id
            || $bukuKasTujuan->user_id !== $user->id
            || $dompetAsal->user_id !== $user->id
            || $dompetTujuan->user_id !== $user->id
            || ! $user->dapatMengelolaTransaksiPada($bukuKasAsal)
            || ! $user->dapatMengelolaTransaksiPada($bukuKasTujuan)
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompetTujuan)
        ) {
            throw new AuthorizationException('Dompet atau buku kas tidak dapat dikelola.');
        }

        return DB::transaction(function () use ($user, $bukuKasAsal, $bukuKasTujuan, $dompetAsal, $dompetTujuan, $nominal, $tanggal, $deskripsi): array {
            $bukuKas = BukuKas::withoutGlobalScopes()
                ->whereKey([$bukuKasAsal->id, $bukuKasTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $dompet = Dompet::withoutGlobalScopes()
                ->whereKey([$dompetAsal->id, $dompetTujuan->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($bukuKas->count() !== 2 || $dompet->count() !== ($dompetAsal->is($dompetTujuan) ? 1 : 2)) {
                throw new AuthorizationException('Dompet atau buku kas tidak tersedia.');
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
                    'dompet_id' => $dompetAsal->id,
                    'jenis' => 'Transfer Pengeluaran',
                    'tujuan_buku_tabungan_id' => $bukuKasTujuan->id,
                ]),
                'masuk' => Transaksi::create($data + [
                    'buku_kas_id' => $bukuKasTujuan->id,
                    'dompet_id' => $dompetTujuan->id,
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
        $this->pastikanDapatMengelola($user, $transaksi);

        $proses = function () use ($user, $transaksi, $data): Transaksi {
            $terkunci = Transaksi::withoutGlobalScopes()->whereKey($transaksi->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->transfer_code) {
                return $this->ubahPasanganTransfer($user, $terkunci, $data);
            }

            $dataBaru = array_merge($terkunci->only([
                'buku_kas_id', 'dompet_id', 'jenis_transaksi_id', 'tanggal', 'nominal', 'jenis', 'deskripsi',
            ]), $data);
            $bukuKasBaru = BukuKas::withoutGlobalScopes()->findOrFail($dataBaru['buku_kas_id']);
            $dompetBaru = Dompet::withoutGlobalScopes()->findOrFail($dataBaru['dompet_id']);

            $this->pastikanTujuanDapatDikelola($user, $bukuKasBaru, $dompetBaru);
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

        foreach ($pasangan as $item) {
            $this->pastikanDapatMengelola($user, $item);
            $this->balikDampak($item);
            Transaksi::withoutEvents(fn () => $item->update([
                'nominal' => $data['nominal'] ?? $item->nominal,
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
        $dompet = Dompet::withoutGlobalScopes()->withTrashed()->findOrFail($transaksi->dompet_id);
        $this->pastikanTujuanDapatDikelola($user, $bukuKas, $dompet);
    }

    private function pastikanTujuanDapatDikelola(User $user, BukuKas $bukuKas, Dompet $dompet): void
    {
        if (
            $dompet->user_id !== $user->id
            || $dompet->trashed()
            || ! $user->dapatMengelolaTransaksiPada($bukuKas)
            || ! $user->dapatMengelolaTransaksiPadaDompet($dompet)
        ) {
            throw new AuthorizationException('Transaksi tidak dapat dikelola.');
        }
    }

    private function pastikanKategoriValid(User $user, ?int $kategoriId, string $jenis): void
    {
        $kategori = $kategoriId
            ? JenisTransaksi::withoutGlobalScopes()->find($kategoriId)
            : null;

        if (! $kategori || $kategori->user_id !== $user->id || $kategori->tipe !== $jenis) {
            throw new AuthorizationException('Kategori transaksi tidak dapat digunakan.');
        }
    }

    private function balikDampak(Transaksi $transaksi): void
    {
        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->decrement('saldo', $dampak);
        Dompet::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->dompet_id)->decrement('saldo', $dampak);
    }

    private function terapkanDampak(Transaksi $transaksi): void
    {
        $dampak = $this->dampak($transaksi);
        BukuKas::withoutGlobalScopes()->whereKey($transaksi->buku_kas_id)->increment('saldo', $dampak);
        Dompet::withoutGlobalScopes()->withTrashed()->whereKey($transaksi->dompet_id)->increment('saldo', $dampak);
    }

    private function dampak(Transaksi $transaksi): int
    {
        return in_array($transaksi->jenis, ['Pengeluaran', 'Transfer Pengeluaran'], true)
            ? -$transaksi->nominal
            : $transaksi->nominal;
    }
}
