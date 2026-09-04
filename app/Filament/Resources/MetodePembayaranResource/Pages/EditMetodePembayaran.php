<?php

namespace App\Filament\Resources\MetodePembayaranResource\Pages;

use App\Filament\Resources\MetodePembayaranResource;
use Filament\Resources\Pages\EditRecord;

class EditMetodePembayaran extends EditRecord
{
    protected static string $resource = MetodePembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
