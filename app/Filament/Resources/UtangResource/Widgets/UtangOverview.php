<?php

namespace App\Filament\Resources\UtangResource\Widgets;

use App\Models\UtangPiutang;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;

class UtangOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListUtangs::class;
    }

    protected function getStats(): array
    {
        return UtangPiutang::stat($this->getPageTableRecords());
    }
}
