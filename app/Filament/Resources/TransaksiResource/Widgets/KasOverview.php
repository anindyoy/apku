<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\SumberDana;

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
        $filterSumberDana = $this->getTablePageInstance()->filterDompet;
        $saldoSumberDana = SumberDana::withTrashed()->find($filterSumberDana)?->saldo;

        $user = auth()->user();
        $hitungTabungan = $user ? $user->hitungTabunganDiTotal() : true;
        $kasQuery = BukuKas::query();
        $labelKas = 'Semua Kas';
        if (! $hitungTabungan) {
            $kasQuery->where('is_tabungan', false);
            $labelKas = 'Kas Biasa';
        }

        return [
            Stat::make(
                'Saldo',
                'Rp ' . number_format($saldoSumberDana ?? $saldoBukuKas)
            )
                ->description($filterSumberDana
                    ? 'Saldo sumber dana terpilih'
                    : $labelKas.' Rp '.number_format($kasQuery->sum('saldo')))
                ->color(($saldoSumberDana ?? $saldoBukuKas) < 0 ? 'danger' : 'primary'),
        ];
    }
}
