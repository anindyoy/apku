<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KategoriService
{
    /**
     * @param  array{nama: string, tipe: string}  $data
     * @param  array<int, int|string>  $idKas
     */
    public function buat(User $user, array $data, array $idKas): Kategori
    {
        $kas = $this->kasYangDipilih($user, $idKas);

        if ($kas->isEmpty() && ! $user->buku_kas()->exists()) {
            throw ValidationException::withMessages(['kas' => 'Pilih minimal satu kas untuk kategori ini.']);
        }

        // Kategori selalu dimiliki pemilik kas, walaupun dibuat oleh Editor.
        $pemilikId = (int) ($kas->first()?->user_id ?? $user->id);
        $nama = trim((string) ($data['nama'] ?? ''));
        $tipe = (string) ($data['tipe'] ?? '');
        $this->pastikanAtributValid($pemilikId, $nama, $tipe);

        return $this->dalamTransaksi(function () use ($user, $kas, $pemilikId, $nama, $tipe): Kategori {
            $kategori = Kategori::withoutGlobalScopes()->create([
                'user_id' => $pemilikId,
                'dibuat_oleh' => $user->id,
                'nama' => $nama,
                'tipe' => $tipe,
            ]);
            $this->hubungkan($kategori, $kas->modelKeys());

            return $kategori;
        });
    }

    /**
     * @param  array{nama?: string, tipe?: string}  $data
     * @param  array<int, int|string>|null  $idKas  Null berarti hubungan kas tidak diubah.
     */
    public function ubah(User $user, Kategori $kategori, array $data, ?array $idKas = null): Kategori
    {
        if (! $user->dapatMengelolaKategori($kategori)) {
            throw new AuthorizationException('Kategori tidak dapat dikelola.');
        }

        $nama = trim((string) ($data['nama'] ?? $kategori->nama));
        $tipe = (string) ($data['tipe'] ?? $kategori->tipe);
        $this->pastikanAtributValid((int) $kategori->user_id, $nama, $tipe, $kategori->id);

        // Seluruh validasi selesai sebelum ada data yang ditulis.
        $perubahanKas = $idKas !== null ? $this->rencanaSinkronKas($user, $kategori, $idKas) : null;

        return $this->dalamTransaksi(function () use ($kategori, $nama, $tipe, $perubahanKas): Kategori {
            $kategori->update(['nama' => $nama, 'tipe' => $tipe]);

            if ($perubahanKas !== null && $perubahanKas['dilepas'] !== []) {
                DB::table('kategori_kas')
                    ->where('kategori_id', $kategori->id)
                    ->whereIn('buku_kas_id', $perubahanKas['dilepas'])
                    ->delete();
                Kategori::bersihkanCacheOpsi($perubahanKas['dilepas']);
            }

            if ($perubahanKas !== null) {
                $this->hubungkan($kategori, $perubahanKas['ditambah']);
            }

            return $kategori->refresh();
        });
    }

    /** Melepas kategori dari satu kas; transaksi yang masih memakainya harus dikosongkan secara eksplisit. */
    public function lepasDariKas(User $user, Kategori $kategori, BukuKas $bukuKas, bool $kosongkanTransaksi = false): int
    {
        if (! $user->dapatMengelolaKategoriPada($bukuKas) || ! $kategori->terhubungKe($bukuKas->id)) {
            throw new AuthorizationException('Kategori tidak dapat dilepas dari kas ini.');
        }

        $transaksi = Transaksi::withoutGlobalScopes()
            ->where('buku_kas_id', $bukuKas->id)
            ->where('kategori_id', $kategori->id);
        $jumlah = (clone $transaksi)->count();

        if ($jumlah > 0 && ! $kosongkanTransaksi) {
            throw ValidationException::withMessages([
                'kas' => $this->pesanMasihDipakai($jumlah, $bukuKas),
            ]);
        }

        return $this->dalamTransaksi(function () use ($kategori, $bukuKas, $transaksi, $jumlah): int {
            $transaksi->update(['kategori_id' => null]);
            DB::table('kategori_kas')
                ->where('kategori_id', $kategori->id)
                ->where('buku_kas_id', $bukuKas->id)
                ->delete();
            Kategori::bersihkanCacheOpsi([$bukuKas->id]);

            return $jumlah;
        });
    }

    /** Menghapus kategori; transaksinya dipindahkan ke kategori pengganti atau menjadi tanpa kategori. */
    public function hapus(User $user, Kategori $kategori, ?int $idPengganti = null): void
    {
        if (! $user->dapatMengelolaKategori($kategori)) {
            throw new AuthorizationException('Kategori tidak dapat dikelola.');
        }

        $pengganti = null;

        if ($idPengganti !== null) {
            $kasTerpakai = Transaksi::withoutGlobalScopes()
                ->where('kategori_id', $kategori->id)
                ->distinct()
                ->pluck('buku_kas_id');
            $pengganti = Kategori::withoutGlobalScopes()->whereKeyNot($kategori->id)->find($idPengganti);

            if (! $pengganti || $kasTerpakai->diff($pengganti->idKas())->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'pengganti' => 'Kategori pengganti harus terhubung ke semua kas yang transaksinya memakai kategori ini.',
                ]);
            }
        }

        $this->dalamTransaksi(function () use ($kategori, $pengganti): void {
            if ($pengganti !== null) {
                Transaksi::withoutGlobalScopes()
                    ->where('kategori_id', $kategori->id)
                    ->update(['kategori_id' => $pengganti->id]);
            }

            // Tanpa pengganti, foreign key mengosongkan kategori pada transaksi terkait.
            $kategori->delete();
        });
    }

    /** @param array<int, int|string> $idKas */
    public function hubungkan(Kategori $kategori, array $idKas): void
    {
        $idKas = array_values(array_unique(array_map('intval', $idKas)));

        if ($idKas === []) {
            return;
        }

        DB::table('kategori_kas')->insertOrIgnore(array_map(fn (int $id): array => [
            'kategori_id' => $kategori->id,
            'buku_kas_id' => $id,
        ], $idKas));
        Kategori::bersihkanCacheOpsi($idKas);
    }

    /** Menghubungkan seluruh kategori milik pemilik kas ke kas yang baru dibuat. */
    public function hubungkanSemuaKategoriPemilik(BukuKas $bukuKas): void
    {
        $idKategori = Kategori::withoutGlobalScopes()->where('user_id', $bukuKas->user_id)->pluck('id');

        if ($idKategori->isEmpty()) {
            return;
        }

        DB::table('kategori_kas')->insertOrIgnore($idKategori->map(fn (int $id): array => [
            'kategori_id' => $id,
            'buku_kas_id' => $bukuKas->id,
        ])->all());
        Kategori::bersihkanCacheOpsi([$bukuKas->id]);
    }

    /**
     * Memvalidasi perubahan hubungan kas tanpa menulis data.
     *
     * @param  array<int, int|string>  $idKas
     * @return array{dilepas: array<int, int>, ditambah: array<int, int>}
     */
    private function rencanaSinkronKas(User $user, Kategori $kategori, array $idKas): array
    {
        $terpilih = $this->kasYangDipilih($user, $idKas);

        if ($terpilih->contains(fn (BukuKas $kas): bool => $kas->user_id !== $kategori->user_id)) {
            throw ValidationException::withMessages([
                'kas' => 'Kategori hanya dapat dihubungkan ke kas milik pemilik yang sama.',
            ]);
        }

        $saatIni = $kategori->idKas();
        // Kas yang tidak dapat dikelola pengguna ini dibiarkan apa adanya.
        $dilepas = BukuKas::withoutGlobalScopes()->whereKey($saatIni)->get()
            ->filter(fn (BukuKas $kas): bool => $user->dapatMengelolaKategoriPada($kas))
            ->reject(fn (BukuKas $kas): bool => $terpilih->contains('id', $kas->id));

        foreach ($dilepas as $kas) {
            $jumlah = Transaksi::withoutGlobalScopes()
                ->where('buku_kas_id', $kas->id)
                ->where('kategori_id', $kategori->id)
                ->count();

            if ($jumlah > 0) {
                throw ValidationException::withMessages(['kas' => $this->pesanMasihDipakai($jumlah, $kas)]);
            }
        }

        return [
            'dilepas' => $dilepas->modelKeys(),
            'ditambah' => array_values(array_diff($terpilih->modelKeys(), $saatIni)),
        ];
    }

    /**
     * @param  array<int, int|string>  $idKas
     * @return Collection<int, BukuKas>
     */
    private function kasYangDipilih(User $user, array $idKas): Collection
    {
        $idKas = array_values(array_unique(array_map('intval', array_filter($idKas))));
        $kas = BukuKas::withoutGlobalScopes()->whereKey($idKas)->get();

        if ($kas->count() !== count($idKas)
            || $kas->contains(fn (BukuKas $item): bool => ! $user->dapatMengelolaKategoriPada($item))) {
            throw new AuthorizationException('Kas tidak dapat dikelola.');
        }

        if ($kas->pluck('user_id')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'kas' => 'Kategori hanya dapat dihubungkan ke kas milik pemilik yang sama.',
            ]);
        }

        return $kas;
    }

    private function pastikanAtributValid(int $pemilikId, string $nama, string $tipe, ?int $abaikanId = null): void
    {
        if ($nama === '' || mb_strlen($nama) > 255) {
            throw ValidationException::withMessages(['nama' => 'Nama kategori wajib diisi dan maksimal 255 karakter.']);
        }

        if (! in_array($tipe, Kategori::TIPE, true)) {
            throw ValidationException::withMessages(['tipe' => 'Tipe kategori tidak valid.']);
        }

        $sudahAda = Kategori::withoutGlobalScopes()
            ->where('user_id', $pemilikId)
            ->where('tipe', $tipe)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->when($abaikanId, fn ($query) => $query->whereKeyNot($abaikanId))
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages(['nama' => 'Kategori dengan nama dan tipe ini sudah ada.']);
        }
    }

    /** Mengikuti pola service lain: transaksi yang sudah berjalan dipakai ulang tanpa savepoint. */
    private function dalamTransaksi(\Closure $proses): mixed
    {
        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    private function pesanMasihDipakai(int $jumlah, BukuKas $bukuKas): string
    {
        return 'Kategori masih dipakai '.number_format($jumlah, 0, ',', '.')." transaksi di kas {$bukuKas->nama_buku}. "
            .'Gunakan aksi Lepas dari kas untuk mengosongkan kategori pada transaksi tersebut terlebih dahulu.';
    }
}
