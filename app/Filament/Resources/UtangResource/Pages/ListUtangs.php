<?php

namespace App\Filament\Resources\UtangResource\Pages;

use App\Models\UtangPiutang;
use App\Filament\Resources\UtangResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\UtangResource\Widgets\UtangOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListUtangs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = UtangResource::class;

    protected function getHeaderActions(): array
    {
        return UtangPiutang::headerActions('utang');
    }

    public function getHeaderWidgets(): array
    {
        return [
            UtangOverview::class
        ];
    }
}
