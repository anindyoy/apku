<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBukuKas extends EditRecord
{
    protected static string $resource = BukuKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
