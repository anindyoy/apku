<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLangganans extends ListRecords
{
    protected static string $resource = LanggananResource::class;

    protected string $view = 'filament.resources.langganan-resource.pages.list-langganans';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat order')
                ->visible(fn (): bool => ! auth()->user()->isAdmin()),
        ];
    }
}
