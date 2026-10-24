<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Concerns\CachesResourceListRecords;
use App\Filament\Resources\BukuKasResource;
use App\Models\Dompet;
use App\Services\DashboardCache;
use App\Services\KategoriService;
use App\Services\KuotaAkun;
use App\Services\TransaksiService;
use Filament\Actions;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBukuKas extends ListRecords
{
    use CachesResourceListRecords;

    protected static string $resource = BukuKasResource::class;

    public bool $hubungkanKategoriKasBaru = false;

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
            Actions\Action::make('pengaturanTampilan')
                ->label('Pengaturan tampilan')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Pengaturan tampilan kas')
                ->modalSubmitActionLabel('Simpan pengaturan')
                ->modalWidth('md')
                ->form(fn (): array => [
                    Toggle::make('hitung_tabungan_di_total')
                        ->label('Hitung saldo tabungan ke total saldo kas')
                        ->helperText('Aktif: saldo kas tabungan diikutsertakan dalam kalkulasi total saldo kas di dashboard. Nonaktif: total saldo kas hanya menghitung kas biasa.'),
                ])
                ->fillForm(fn (): array => ['hitung_tabungan_di_total' => auth()->user()->hitungTabunganDiTotal()])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $user->forceFill(['hitung_tabungan_di_total' => (bool) $data['hitung_tabungan_di_total']])->save();
                    Notification::make()->title('Pengaturan kas disimpan')->success()->send();
                }),

            Actions\CreateAction::make()
                ->modalDescription(fn (): Htmlable => KuotaAkun::kas(auth()->user()))
                ->visible(fn (): bool => auth()->user()->dapatMembuatBukuKas())
                ->before(function (): void {
                    abort_unless(auth()->user()->dapatMembuatBukuKas(), 403);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    // Pilihan ini bukan kolom kas, jadi disimpan sementara sampai kas selesai dibuat.
                    $this->hubungkanKategoriKasBaru = (bool) ($data['hubungkan_kategori'] ?? false);
                    unset($data['hubungkan_kategori']);

                    return $data;
                })
                ->after(function ($record): void {
                    if ($this->hubungkanKategoriKasBaru) {
                        app(KategoriService::class)->hubungkanSemuaKategoriPemilik($record);
                    }

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
