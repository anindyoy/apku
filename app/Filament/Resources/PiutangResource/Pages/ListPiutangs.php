<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Models\UtangPiutang;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PiutangResource;
use App\Filament\Resources\PiutangResource\Widgets\PiutangOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListPiutangs extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return UtangPiutang::headerActions('piutang');
    }

    public function getHeaderWidgets(): array
    {
        return [
            PiutangOverview::class
        ];
    }
}
