<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use App\Models\Dompet;
use App\Services\TransaksiService;
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
                ->after(function ($record): void {
                    $saldoAwal = (int) $record->saldo;
                    $record->update(['saldo' => 0]);
                    $dompet = Dompet::findOrFail(auth()->user()->idDompetUtama());

                    app(TransaksiService::class)->buatSaldoAwal(
                        auth()->user(),
                        $record,
                        $dompet,
                        $saldoAwal,
                        'Saldo pertama',
                    );
                }),
        ];
    }
}
