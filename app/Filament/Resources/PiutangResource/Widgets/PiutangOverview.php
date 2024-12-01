<?php

namespace App\Filament\Resources\PiutangResource\Widgets;

use App\Models\UtangPiutang;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;

class PiutangOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListPiutangs::class;
    }

    protected function getStats(): array
    {
        return UtangPiutang::stat($this->getPageTableRecords());
    }

}
