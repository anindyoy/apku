<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BukuKasService
{
    /**
     * Kategori pada transaksi kas asal yang belum terhubung ke kas tujuan dan perlu dipetakan pengguna.
     *
     * @return array<int, string>
     */
    public function kategoriPerluDipetakan(BukuKas $asal, BukuKas $tujuan): array
    {
        return Kategori::withoutGlobalScopes()
            ->whereIn('id', Transaksi::withoutGlobalScopes()
                ->where('buku_kas_id', $asal->id)
                ->whereNotNull('kategori_id')
                ->select('kategori_id'))
            ->whereNotIn('id', DB::table('kategori_kas')->where('buku_kas_id', $tujuan->id)->select('kategori_id'))
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /**
     * Memindahkan transaksi, saldo, dan tabungan emas ke kas tujuan lalu menghapus kas asal.
     *
     * @param  array<int|string, int|string|null>  $pemetaanKategori  ID kategori asal => ID kategori di kas tujuan; kosong berarti tanpa kategori.
     */
    public function pindahkanDanHapus(User $user, BukuKas $asal, BukuKas $tujuan, array $pemetaanKategori = []): void
    {
        if ($asal->user_id !== $user->id || $tujuan->user_id !== $user->id || $asal->is($tujuan)) {
            throw new AuthorizationException('Kas tidak dapat dipindahkan.');
        }

        $kategoriTujuan = DB::table('kategori_kas')
            ->where('buku_kas_id', $tujuan->id)
            ->pluck('kategori_id')
            ->map(fn ($id): int => (int) $id);
        $pemetaan = [];

        // Seluruh pemetaan divalidasi sebelum ada transaksi yang dipindahkan.
        foreach (array_keys($this->kategoriPerluDipetakan($asal, $tujuan)) as $idKategori) {
            $pengganti = $pemetaanKategori[$idKategori] ?? null;
            $pengganti = filled($pengganti) ? (int) $pengganti : null;

            if ($pengganti !== null && ! $kategoriTujuan->contains($pengganti)) {
                throw ValidationException::withMessages([
                    'pemetaan_kategori' => 'Kategori pengganti harus terhubung ke kas tujuan.',
                ]);
            }

            $pemetaan[$idKategori] = $pengganti;
        }

        $proses = function () use ($asal, $tujuan, $pemetaan): void {
            $asal = BukuKas::withoutGlobalScopes()->whereKey($asal->id)->lockForUpdate()->firstOrFail();
            $tujuan = BukuKas::withoutGlobalScopes()->whereKey($tujuan->id)->lockForUpdate()->firstOrFail();

            foreach ($pemetaan as $idKategori => $pengganti) {
                Transaksi::withoutGlobalScopes()
                    ->where('buku_kas_id', $asal->id)
                    ->where('kategori_id', $idKategori)
                    ->update(['kategori_id' => $pengganti]);
            }

            Transaksi::withoutGlobalScopes()->where('buku_kas_id', $asal->id)->update(['buku_kas_id' => $tujuan->id]);
            $asal->tabunganEmas()->update(['buku_kas_id' => $tujuan->id]);
            $tujuan->increment('saldo', $asal->saldo);
            Kategori::bersihkanCacheOpsi([$asal->id, $tujuan->id]);
            $asal->delete();
        };

        DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }
}
