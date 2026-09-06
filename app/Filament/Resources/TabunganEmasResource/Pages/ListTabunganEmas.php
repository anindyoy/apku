<?php

namespace App\Filament\Resources\TabunganEmasResource\Pages;

use App\Filament\Resources\TabunganEmasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTabunganEmas extends ListRecords
{
    protected static string $resource = TabunganEmasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => $data + ['berat_gram' => 0, 'total_modal' => 0]),
        ];
    }
}
