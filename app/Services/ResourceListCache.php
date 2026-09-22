<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResourceListCache
{
    private const DEPENDENCIES = [
        'kas' => ['buku_kas', 'transaksi', 'tabungan_emas', 'share_buku', 'users'],
        'dompet' => ['dompet', 'users'],
    ];

    public static function remember(string $section, int $userId, array $state, Closure $load, ?Carbon $expiresAt = null): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $load();
        }

        $versions = [];
        foreach (self::DEPENDENCIES[$section] as $table) {
            $versions[] = DashboardCache::tableVersion($table);
        }

        $key = 'resource-list:'.$section.':'.$userId.':'.hash('sha256', serialize([$state, $versions]));

        return Cache::remember($key, $expiresAt ?? now()->addDays(3), $load);
    }
}
