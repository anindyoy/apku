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
        foreach (self::varian($entitas) as $varian) {
            Cache::forget(self::key($entitas, $userId, $varian));
        }
    }

    private static function key(string $entitas, ?int $userId, string $varian): string
    {
        return "opsi-select:{$entitas}:".($userId ?? 'global').":{$varian}";
    }

    private static function varian(string $entitas): array
    {
        return match ($entitas) {
            'buku-kas' => ['default', 'dapat-dikelola'],
            'dompet' => ['aktif', 'dapat-dikelola', 'dengan-terhapus'],
            'jenis-transaksi' => ['Pemasukan', 'Pengeluaran'],
            default => ['default'],
        };
    }
}
