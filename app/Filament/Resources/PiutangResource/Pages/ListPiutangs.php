<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use Filament\Actions;
use App\Models\UtangPiutang;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PiutangResource;

class ListPiutangs extends ListRecords
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return UtangPiutang::headerActions('piutang');
    }
}
