<?php

namespace App\Filament\Resources\UtangResource\Pages;

use App\Filament\Resources\UtangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUtangs extends ListRecords
{
    protected static string $resource = UtangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
