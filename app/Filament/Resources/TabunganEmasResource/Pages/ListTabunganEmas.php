<?php

namespace App\Filament\Resources\TabunganEmasResource\Pages;

use App\Filament\Resources\TabunganEmasResource;
use App\Models\TabunganEmas;
use App\Services\TabunganEmasService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListTabunganEmas extends ListRecords
{
    protected static string $resource = TabunganEmasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data): TabunganEmas => DB::transaction(function () use ($data): TabunganEmas {
                    $berat = (float) $data['berat_gram'];
                    $data['berat_gram'] = 0;
                    $data['total_modal'] = 0;
                    $tabungan = TabunganEmas::create($data);
                    app(TabunganEmasService::class)->catatSaldoAwal(auth()->user(), $tabungan, $berat, 0);

                    return $tabungan->refresh();
                })),
        ];
    }
}
