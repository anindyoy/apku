<?php

namespace App\Filament\Resources\PaketLanggananResource\Pages;

use App\Filament\Resources\PaketLanggananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaketLangganans extends ListRecords
{
    protected static string $resource = PaketLanggananResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
