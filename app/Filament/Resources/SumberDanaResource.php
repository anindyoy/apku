<?php

namespace App\Filament\Resources;

use App\Models\SumberDana;

class SumberDanaResource extends DompetResource
{
    protected static ?string $model = SumberDana::class;

    protected static ?string $navigationLabel = 'Sumber Dana';

    protected static ?string $slug = 'dompet';
}
