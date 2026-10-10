<?php

namespace App\Filament\Resources\TrialPremiumResource\Pages;

use App\Filament\Resources\TrialPremiumResource;
use Filament\Resources\Pages\ListRecords;

// Halaman daftar baca-saja; tanpa aksi tambah karena trial dibuat oleh user.
class ListTrialPremiums extends ListRecords
{
    protected static string $resource = TrialPremiumResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
