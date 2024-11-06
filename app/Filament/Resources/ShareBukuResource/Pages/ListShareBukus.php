<?php

namespace App\Filament\Resources\ShareBukuResource\Pages;

use App\Filament\Resources\ShareBukuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShareBukus extends ListRecords
{
    protected static string $resource = ShareBukuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
