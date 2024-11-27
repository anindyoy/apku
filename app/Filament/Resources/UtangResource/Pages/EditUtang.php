<?php

namespace App\Filament\Resources\UtangResource\Pages;

use App\Filament\Resources\UtangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUtang extends EditRecord
{
    protected static string $resource = UtangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
