<?php

namespace App\Filament\Resources\JenisTransaksiResource\Pages;

use App\Filament\Resources\JenisTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisTransaksi extends EditRecord
{
    protected static string $resource = JenisTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
