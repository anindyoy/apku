<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\UtangResource\Pages\UtangPiutangDetail;
use App\Models\UtangPiutangDetail as ModelsUtangPiutangDetail;

class UtangPiutangDetailOverview extends BaseWidget
{
    use InteractsWithPageTable;

    public $record;

    protected function getTablePage(): string
    {
        return UtangPiutangDetail::class;
    }

    protected function getStats(): array
    {
        $sisa = ModelsUtangPiutangDetail::whereUtangPiutangId($this->record)->tambah()
            ->sum('nominal') - ModelsUtangPiutangDetail::whereUtangPiutangId($this->record)->kurang()
            ->sum('nominal');

        return [
            Stat::make(
                'Total',
                'Rp ' . number_format($sisa)
            ),
        ];
    }
}
