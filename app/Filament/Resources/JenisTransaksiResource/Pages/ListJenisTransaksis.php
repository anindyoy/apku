<?php

namespace App\Filament\Resources\JenisTransaksiResource\Pages;

use App\Filament\Resources\JenisTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisTransaksis extends ListRecords
{
    protected static string $resource = JenisTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
