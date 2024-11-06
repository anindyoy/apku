<?php

namespace App\Filament\Resources\UtangPiutangResource\Pages;

use App\Filament\Resources\UtangPiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUtangPiutang extends EditRecord
{
    protected static string $resource = UtangPiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
