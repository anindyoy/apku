<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;

class KasOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListTransaksis::class;
    }

    protected function getStats(): array
    {
        $filterBukuKas = $this->getTablePageInstance()->filterBukuKas;
        $saldoBukuKas = BukuKas::find($filterBukuKas)?->saldo ?? 0;

        return [
            Stat::make(
                'Saldo',
                'Rp ' . number_format($saldoBukuKas)
            )
                ->description('Semua Buku Kas Rp ' . number_format(BukuKas::sum('saldo'))),
        ];
    }
}
