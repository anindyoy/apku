<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPiutang extends EditRecord
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
