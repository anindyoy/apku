<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\SumberDana;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class KasOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = false;

    #[Reactive]
    public ?string $filterBukuKas = null;

    #[Reactive]
    public ?string $filterDompet = null;

    protected function getTablePage(): string
    {
        return ListTransaksis::class;
    }

    protected function getStats(): array
    {
        // Gunakan properti reaktif dari halaman tabel agar filter tetap terbaca saat pembaruan Livewire.
        $filterBukuKas = $this->filterBukuKas;
        $saldoBukuKas = BukuKas::find($filterBukuKas)?->saldo ?? 0;
        $filterSumberDana = $this->filterDompet;
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
