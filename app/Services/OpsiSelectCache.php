<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class OpsiSelectCache
{
    private const TTL_HARI = 3;

    public static function ingat(string $entitas, Closure $query, ?int $userId = null, string $varian = 'default'): array
    {
        return Cache::remember(
            self::key($entitas, $userId, $varian),
            now()->addDays(self::TTL_HARI),
            $query,
        );
    }

    public static function bersihkan(string $entitas, ?int $userId = null): void
    {
        $daftarEntitas = match ($entitas) {
            'sumber_dana', 'dompet' => ['sumber_dana', 'dompet'],
            default => [$entitas],
        };

        foreach ($daftarEntitas as $item) {
            foreach (self::varian($item) as $varian) {
                Cache::forget(self::key($item, $userId, $varian));
            }
        }
    }

    private static function key(string $entitas, ?int $userId, string $varian): string
    {
        return "opsi-select:{$entitas}:".($userId ?? 'global').":{$varian}";
    }

    private static function varian(string $entitas): array
    {
        return match ($entitas) {
            'buku-kas' => ['default', 'dapat-dikelola', 'milik-sendiri', 'transfer', 'kelola-kategori'],
            // Kunci dompet dipertahankan untuk kompatibilitas test lama; entitas baru memakai sumber_dana.
            'dompet' => ['default', 'aktif', 'dapat-dikelola', 'dengan-terhapus'],
            'sumber_dana' => ['default', 'aktif', 'dapat-dikelola', 'dengan-terhapus'],
            'kategori' => ['Pemasukan', 'Pengeluaran', 'semua', 'dengan-tipe', 'pengganti'],
            default => ['default'],
        };
    }
}
