<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use App\Models\BukuKas;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;

class KasOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListTransaksis::class;
    }

    protected function getStats(): array
    {
        // dd($this->getPageTableRecords()->first());
        return [
            Stat::make(
                'Saldo',
                'Rp ' . number_format($this->getPageTableRecords()->first()->buku_kas?->saldo)
            )
                ->description('Semua Buku Kas Rp ' . number_format(BukuKas::sum('saldo'))),
        ];
    }
}
