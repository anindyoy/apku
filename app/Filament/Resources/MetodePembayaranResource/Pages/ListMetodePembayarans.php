<?php

namespace App\Filament\Resources\MetodePembayaranResource\Pages;

use App\Filament\Resources\MetodePembayaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMetodePembayarans extends ListRecords
{
    protected static string $resource = MetodePembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
