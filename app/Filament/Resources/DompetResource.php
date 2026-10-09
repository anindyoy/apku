<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Models\Dompet;

// Sumber kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
// Seluruh logika tabel dan form memakai SumberDanaResource agar perilaku tetap sama.
class DompetResource extends SumberDanaResource
{
    protected static ?string $model = Dompet::class;

    protected static ?string $slug = 'dompet';

    public static function getPages(): array
    {
        return ['index' => ListDompet::route('/')];
    }
}
