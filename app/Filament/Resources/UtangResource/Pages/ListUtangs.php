<?php

namespace App\Filament\Resources\UtangResource\Pages;

use Filament\Actions;
use App\Models\UtangPiutangDetail;
use Filament\Support\Enums\MaxWidth;
use Filament\Pages\Actions\CreateAction;
use App\Filament\Resources\UtangResource;
use App\Models\UtangPiutang;
use Filament\Resources\Pages\ListRecords;

class ListUtangs extends ListRecords
{
    protected static string $resource = UtangResource::class;

    protected function getHeaderActions(): array
    {
        return UtangPiutang::headerActions('utang');
    }
}
