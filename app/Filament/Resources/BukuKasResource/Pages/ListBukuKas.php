<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use Filament\Actions;
use App\Models\Transaksi;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\BukuKasResource;

class ListBukuKas extends ListRecords
{
    protected static string $resource = BukuKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();

                    return $data;
                })
                ->after(function ($record) {
                    Transaksi::create([
                        'user_id' => $record->user_id,
                        'buku_kas_id' => $record->id,
                        'tanggal' => now(),
                        'nominal' => $record->saldo,
                        'jenis' => 'Pemasukan',
                        'deskripsi' => 'Saldo pertama',
                    ]);
                }),
        ];
    }
}
