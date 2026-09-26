<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Concerns\CachesResourceListRecords;
use App\Filament\Resources\BukuKasResource;
use App\Models\Dompet;
use App\Services\KuotaAkun;
use App\Services\TransaksiService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBukuKas extends ListRecords
{
    use CachesResourceListRecords;

    protected static string $resource = BukuKasResource::class;

    public function getSubheading(): Htmlable
    {
        return KuotaAkun::kas(auth()->user());
    }

    protected function resourceListCacheSection(): string
    {
        return 'kas';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalDescription(fn (): Htmlable => KuotaAkun::kas(auth()->user()))
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
