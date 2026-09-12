<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardCache
{
    private const DEPENDENCIES = [
        'kas' => ['buku_kas'],
        'dompet' => ['dompet'],
        'utang' => ['utang_piutang', 'utang_piutang_detail'],
        'piutang' => ['utang_piutang', 'utang_piutang_detail'],
        'langganan' => ['users', 'langganans'],
        'settings' => ['users'],
        'transaksi' => ['transaksi', 'users', 'buku_kas', 'dompet', 'jenis_transaksi', 'share_buku'],
        'admin' => ['users', 'langganans'],
    ];

    public static function remember(string $section, int $userId, Closure $load): mixed
    {
        // Data dalam transaksi belum boleh dibagikan kepada request lain.
        if (DB::transactionLevel() > 0) {
            return $load();
        }

        $versions = [];
        foreach (self::DEPENDENCIES[$section] as $table) {
            $key = 'dashboard:version:'.$table;
            Cache::add($key, (string) Str::uuid());
            $versions[] = Cache::get($key);
        }

        // Tanggal membatasi cache status langganan saat berganti hari.
        $key = 'dashboard:'.$userId.':'.$section.':'.today()->toDateString().':'.hash('sha256', implode('|', $versions));

        return Cache::remember($key, now()->addMinutes(30), $load);
    }

    public static function invalidateWrite(QueryExecuted $event): void
    {
        // Pantau penulisan langsung juga karena service saldo memakai withoutEvents dan bulk update.
        if (! preg_match('/^\s*(?:insert(?:\s+ignore)?\s+into|replace\s+into|update|delete\s+from|truncate(?:\s+table)?)\s+[`"]?([a-z_]+)/i', $event->sql, $match)) {
            return;
        }
        $table = strtolower($match[1]);
        if (! in_array($table, array_merge(...array_values(self::DEPENDENCIES)), true)) {
            return;
        }

        $invalidate = static fn () => Cache::forever('dashboard:version:'.$table, (string) Str::uuid());
        $invalidate();
        if ($event->connection->transactionLevel() > 0) {
            // Invalidasi ulang setelah commit menutup celah pembacaan data lama selama transaksi.
            $event->connection->afterCommit($invalidate);
        }
    }
}
