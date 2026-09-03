<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;

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
        $filterDompet = $this->getTablePageInstance()->filterDompet;
        $saldoDompet = Dompet::withTrashed()->find($filterDompet)?->saldo;

        return [
            Stat::make(
                'Saldo',
                'Rp ' . number_format($saldoDompet ?? $saldoBukuKas)
            )
                ->description($filterDompet
                    ? 'Saldo dompet terpilih'
                    : 'Semua Buku Kas Rp '.number_format(BukuKas::sum('saldo')))
                ->color(($saldoDompet ?? $saldoBukuKas) < 0 ? 'danger' : 'primary'),
        ];
    }
}
