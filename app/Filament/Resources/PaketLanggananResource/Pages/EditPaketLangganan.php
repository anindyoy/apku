<?php

namespace App\Filament\Resources\PaketLanggananResource\Pages;

use App\Filament\Resources\PaketLanggananResource;
use Filament\Resources\Pages\EditRecord;

class EditPaketLangganan extends EditRecord
{
    protected static string $resource = PaketLanggananResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
