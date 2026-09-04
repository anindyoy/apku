<?php

namespace App\Models\Concerns;

use App\Services\OpsiSelectCache;

trait MembersihkanCacheOpsiSelect
{
    protected static function bootMembersihkanCacheOpsiSelect(): void
    {
        $bersihkan = function ($model): void {
            OpsiSelectCache::bersihkan(
                $model->cacheOpsiSelectEntitas(),
                $model->cacheOpsiSelectPerUser() ? (int) $model->user_id : null,
            );
        };

        static::saved($bersihkan);
        static::deleted($bersihkan);
        static::registerModelEvent('restored', $bersihkan);
    }

    abstract protected function cacheOpsiSelectEntitas(): string;

    protected function cacheOpsiSelectPerUser(): bool
    {
        return true;
    }
}
