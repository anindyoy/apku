<?php

namespace App\Filament\Resources\ShareBukuResource\Pages;

use App\Filament\Resources\ShareBukuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShareBuku extends EditRecord
{
    protected static string $resource = ShareBukuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
