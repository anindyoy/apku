<?php

namespace App\Filament\Concerns;

use App\Models\ShareBuku;
use App\Services\ResourceListCache;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait CachesResourceListRecords
{
    abstract protected function resourceListCacheSection(): string;

    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        if ($this->cachedTableRecords) {
            return $this->cachedTableRecords;
        }

        $state = [
            'search' => $this->getTableSearch(),
            'column_searches' => $this->getTableColumnSearches(),
            'filters' => $this->tableFilters,
            'sort' => $this->tableSort,
            'page' => $this->getTablePage(),
            'per_page' => $this->getTableRecordsPerPage(),
            'columns' => $this->tableColumns,
        ];

        return $this->cachedTableRecords = ResourceListCache::remember(
            $this->resourceListCacheSection(),
            auth()->id(),
            $state,
            fn (): Collection|Paginator|CursorPaginator => parent::getTableRecords(),
            $this->resourceListCacheExpiresAt(),
        );
    }

    protected function resourceListCacheExpiresAt(): Carbon
    {
        $expiresAt = now()->addDays(3);

        if ($this->resourceListCacheSection() !== 'kas' || auth()->user()->isAdmin()) {
            return $expiresAt;
        }

        // Akses kas bersama berubah saat tanggal mulai atau berakhir tercapai.
        $shares = ShareBuku::query()
            ->where('user_id', auth()->id())
            ->where(fn ($query) => $query->where('berlaku_mulai', '>', now())->orWhere('berlaku_sampai', '>', now()))
            ->get(['berlaku_mulai', 'berlaku_sampai']);

        foreach ($shares as $share) {
            foreach ([$share->berlaku_mulai, $share->berlaku_sampai] as $boundary) {
                if ($boundary?->isFuture() && $boundary->lessThan($expiresAt)) {
                    $expiresAt = $boundary;
                }
            }
        }

        return $expiresAt;
    }
}
