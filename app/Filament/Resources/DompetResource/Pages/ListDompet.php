<?php

namespace App\Filament\Resources\DompetResource\Pages;

use App\Filament\Resources\DompetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDompet extends ListRecords
{
    protected static string $resource = DompetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()->dapatMembuatDompet())
                ->before(fn () => abort_unless(auth()->user()->dapatMembuatDompet(), 403))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $data['is_default'] = ! auth()->user()->dompet()->exists();

                    return $data;
                }),
        ];
    }
}
