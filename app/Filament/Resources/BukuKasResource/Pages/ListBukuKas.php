<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use App\Models\Transaksi;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBukuKas extends ListRecords
{
    protected static string $resource = BukuKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => auth()->user()->dapatMembuatBukuKas())
                ->before(function (): void {
                    abort_unless(auth()->user()->dapatMembuatBukuKas(), 403);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();

                    return $data;
                })
                ->after(function ($record) {
                    Transaksi::create([
                        'user_id' => $record->user_id,
                        'buku_kas_id' => $record->id,
                        'dompet_id' => auth()->user()->idDompetUtama(),
                        'tanggal' => now(),
                        'nominal' => $record->saldo,
                        'jenis' => 'Pemasukan',
                        'deskripsi' => 'Saldo pertama',
                    ]);
                }),
        ];
    }
}
