<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBukuKas extends ListRecords
{
    protected static string $resource = BukuKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
