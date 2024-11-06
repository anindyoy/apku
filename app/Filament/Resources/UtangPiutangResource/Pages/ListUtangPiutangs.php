<?php

namespace App\Filament\Resources\UtangPiutangResource\Pages;

use App\Filament\Resources\UtangPiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUtangPiutangs extends ListRecords
{
    protected static string $resource = UtangPiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
