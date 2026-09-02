<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\Transaksi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class ListTransaksis extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransaksiResource::class;

    protected static ?string $navigationLabel = 'Transaksi';

    protected string $view = 'filament.resources.transaksi-resource.pages.list-transaksis';

    public $list_kas = [];

    public string $filterMonth = '';

    public string|int $filterYear = '';

    public ?string $filterBukuKas = null;

    public function mount(?string $filterBukuKas = null): void
    {
        $this->filterMonth = request()->query('filter_month', date('m'));
        $this->filterYear = request()->query('filter_year', date('Y'));
        $requestedBukuKas = $filterBukuKas ?? request()->query('filter_buku_kas');
        $this->filterBukuKas = filled($requestedBukuKas) ? (string) $requestedBukuKas : null;

        $this->authorizeAccess();
    }

    public function getPreviousPeriodUrl(): string
    {
        $month = (int) $this->filterMonth - 1;
        $year = (int) $this->filterYear;

        if ($month < 1) {
            $month = 12;
            $year--;
        }

        return static::getUrl(parameters: [
            'filter_month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'filter_year' => $year,
            'filter_buku_kas' => $this->filterBukuKas,
        ]);
    }

    public function getNextPeriodUrl(): string
    {
        $month = (int) $this->filterMonth + 1;
        $year = (int) $this->filterYear;

        if ($month > 12) {
            $month = 1;
            $year++;
        }

        return static::getUrl(parameters: [
            'filter_month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'filter_year' => $year,
            'filter_buku_kas' => $this->filterBukuKas,
        ]);
    }

    public function getBukuKasOptions(): array
    {
        return BukuKas::orderByRaw("CASE WHEN nama_buku = 'Kas Utama' THEN 1 ELSE 2 END")
            ->orderBy('nama_buku')
            ->pluck('nama_buku', 'id')
            ->toArray();
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        $query = parent::getTableQuery();

        if ($query && ! empty($this->filterMonth) && ! empty($this->filterYear)) {
            $query->whereMonth('tanggal', $this->filterMonth)
                ->whereYear('tanggal', $this->filterYear);
        }

        if ($query && ! empty($this->filterBukuKas)) {
            $query->where('buku_kas_id', $this->filterBukuKas);
        }

        return $query;
    }

    public function defaultForm($livewire)
    {
        return [
            'buku_kas_id' => $this->filterBukuKas ?: optional(BukuKas::first())->id,
            'tanggal' => date('d M Y, H:i:s'),
        ];
    }

    public function dapatMengelolaBukuKasTerpilih(): bool
    {
        $bukuKas = BukuKas::find($this->filterBukuKas);

        return $bukuKas !== null
            && auth()->user()->dapatMengelolaTransaksiPada($bukuKas);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Transfer saldo')
                ->visible(fn (): bool => $this->dapatMengelolaBukuKasTerpilih())
                ->before(function (): void {
                    abort_unless($this->dapatMengelolaBukuKasTerpilih(), 403);
                })
                ->tooltip('Transfer saldo ke kas lain')
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    DB::transaction(function () use ($data) {
                        $data['user_id'] = auth()->user()->id;
                        $tujuan_id = $data['buku_kas_id_tujuan'];
                        $asal_id = $data['buku_kas_id'];

                        abort_unless(
                            auth()->user()->dapatMengelolaTransaksiPada(BukuKas::findOrFail($asal_id))
                            && auth()->user()->dapatMengelolaTransaksiPada(BukuKas::findOrFail($tujuan_id)),
                            403
                        );

                        unset($data['buku_kas_id_tujuan']);

                        $transfer_code = uniqid();

                        $data['jenis'] = 'Transfer Pengeluaran';
                        $data['transfer_code'] = $transfer_code;
                        $data['tujuan_buku_tabungan_id'] = $tujuan_id;
                        Transaksi::create($data);

                        $data['jenis'] = 'Transfer Pemasukan';
                        $data['transfer_code'] = $transfer_code;
                        $data['buku_kas_id'] = $tujuan_id;
                        $data['asal_buku_tabungan_id'] = $asal_id;
                        Transaksi::create($data);
                    });

                    Notification::make()
                        ->title('Berhasil Transfer Saldo')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn ($livewire): array => $this->defaultForm($livewire))
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form(true))
                ->color('primary')
                ->icon('heroicon-o-arrow-path-rounded-square'),

            Action::make('Catat Pemasukan')
                ->visible(fn (): bool => $this->dapatMengelolaBukuKasTerpilih())
                ->before(function (): void {
                    abort_unless($this->dapatMengelolaBukuKasTerpilih(), 403);
                })
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    $bukuKas = BukuKas::findOrFail($data['buku_kas_id']);
                    abort_unless(auth()->user()->dapatMengelolaTransaksiPada($bukuKas), 403);

                    $data['jenis'] = 'Pemasukan';
                    $data['user_id'] = auth()->user()->id;
                    Transaksi::create($data);

                    Notification::make()
                        ->title('Berhasil Catat Pemasukan')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn ($livewire): array => $this->defaultForm($livewire))
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form())
                ->color('success')
                ->icon('heroicon-o-arrow-down-on-square'),

            Action::make('Catat Pengeluaran')
                ->visible(fn (): bool => $this->dapatMengelolaBukuKasTerpilih())
                ->before(function (): void {
                    abort_unless($this->dapatMengelolaBukuKasTerpilih(), 403);
                })
                ->action(function (?Transaksi $record, array $data, $livewire, $form, $action, array $arguments) {
                    $bukuKas = BukuKas::findOrFail($data['buku_kas_id']);
                    abort_unless(auth()->user()->dapatMengelolaTransaksiPada($bukuKas), 403);

                    $data['jenis'] = 'Pengeluaran';
                    $data['user_id'] = auth()->user()->id;

                    Transaksi::create($data);
                    Notification::make()
                        ->title('Berhasil Catat Pengeluaran')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn (): array => [
                    'buku_kas_id' => $this->filterBukuKas ?: optional(BukuKas::first())->id,
                    'tanggal' => now(),
                ])
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form())
                ->color('danger')
                ->icon('heroicon-o-arrow-up-on-square'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return filled($this->filterBukuKas) ? [KasOverview::class] : [];
    }
}
