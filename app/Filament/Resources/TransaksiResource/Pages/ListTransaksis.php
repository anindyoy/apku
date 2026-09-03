<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\TransferDompetService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public ?string $filterDompet = null;

    public function mount(?string $filterBukuKas = null): void
    {
        $this->filterMonth = request()->query('filter_month', date('m'));
        $this->filterYear = request()->query('filter_year', date('Y'));
        $requestedBukuKas = $filterBukuKas ?? request()->query('filter_buku_kas');
        $this->filterBukuKas = filled($requestedBukuKas) ? (string) $requestedBukuKas : null;
        $requestedDompet = request()->query('filter_dompet');
        $this->filterDompet = filled($requestedDompet) && Dompet::withTrashed()->whereKey($requestedDompet)->exists()
            ? (string) $requestedDompet
            : null;

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
            'filter_dompet' => $this->filterDompet,
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
            'filter_dompet' => $this->filterDompet,
        ]);
    }

    public function getBukuKasOptions(): array
    {
        return BukuKas::orderByRaw("CASE WHEN nama_buku = 'Kas Utama' THEN 1 ELSE 2 END")
            ->orderBy('nama_buku')
            ->pluck('nama_buku', 'id')
            ->toArray();
    }

    public function getDompetOptions(): array
    {
        return Dompet::withTrashed()
            ->orderByDesc('is_default')
            ->orderBy('nama_dompet')
            ->get()
            ->mapWithKeys(fn (Dompet $dompet): array => [
                $dompet->id => $dompet->nama_dompet.($dompet->trashed() ? ' (Dihapus)' : ''),
            ])
            ->all();
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

        if ($query && ! empty($this->filterDompet)) {
            $query->where('dompet_id', $this->filterDompet);
        }

        return $query;
    }

    public function dataAwalTransaksi($livewire): array
    {
        return [
            'buku_kas_id' => $this->filterBukuKas ?: optional(BukuKas::first())->id,
            'dompet_id' => $this->filterDompet ?: auth()->user()->idDompetUtama(),
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
            Action::make('Pindah saldo dompet')
                ->visible(fn (): bool => count(Transaksi::opsiDompetYangDapatDikelola()) >= 2)
                ->schema([
                    Select::make('dompet_asal_id')
                        ->label('Dompet asal')
                        ->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola())
                        ->default(fn (): ?int => $this->filterDompet ? (int) $this->filterDompet : auth()->user()->idDompetUtama())
                        ->required(),
                    Select::make('dompet_tujuan_id')
                        ->label('Dompet tujuan')
                        ->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola())
                        ->different('dompet_asal_id')
                        ->required(),
                    Select::make('buku_kas_id')
                        ->label('Buku kas pencatatan')
                        ->options(fn (): array => BukuKas::all()
                            ->filter(fn (BukuKas $bukuKas): bool => auth()->user()->dapatMengelolaTransaksiPada($bukuKas))
                            ->pluck('nama_buku', 'id')
                            ->all())
                        ->default(fn (): ?int => $this->filterBukuKas ? (int) $this->filterBukuKas : auth()->user()->idBukuKasUtama())
                        ->required(),
                    DateTimePicker::make('tanggal')->required()->default(now())->seconds(false),
                    TextInput::make('nominal')->required()->numeric()->minValue(1)->prefix('Rp'),
                    TextInput::make('deskripsi'),
                ])
                ->action(function (array $data): void {
                    app(TransferDompetService::class)->transfer(
                        auth()->user(),
                        Dompet::findOrFail($data['dompet_asal_id']),
                        Dompet::findOrFail($data['dompet_tujuan_id']),
                        BukuKas::findOrFail($data['buku_kas_id']),
                        (int) $data['nominal'],
                        $data['tanggal'],
                        $data['deskripsi'] ?? null,
                    );

                    Notification::make()->title('Saldo dompet berhasil dipindahkan')->success()->send();
                })
                ->color('warning')
                ->icon('heroicon-o-arrows-right-left'),

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
                        $dompetTujuanId = $data['dompet_id_tujuan'];

                        abort_unless(
                            auth()->user()->dapatMengelolaTransaksiPada(BukuKas::findOrFail($asal_id))
                            && auth()->user()->dapatMengelolaTransaksiPada(BukuKas::findOrFail($tujuan_id))
                            && auth()->user()->dapatMengelolaTransaksiPadaDompet(Dompet::findOrFail($data['dompet_id']))
                            && auth()->user()->dapatMengelolaTransaksiPadaDompet(Dompet::findOrFail($dompetTujuanId)),
                            403
                        );

                        unset($data['buku_kas_id_tujuan'], $data['dompet_id_tujuan']);

                        $transfer_code = (string) Str::uuid();

                        $data['jenis'] = 'Transfer Pengeluaran';
                        $data['transfer_code'] = $transfer_code;
                        $data['tipe_transfer'] = 'buku_kas';
                        $data['tujuan_buku_tabungan_id'] = $tujuan_id;
                        Transaksi::create($data);

                        $data['jenis'] = 'Transfer Pemasukan';
                        $data['transfer_code'] = $transfer_code;
                        $data['buku_kas_id'] = $tujuan_id;
                        $data['dompet_id'] = $dompetTujuanId;
                        $data['asal_buku_tabungan_id'] = $asal_id;
                        Transaksi::create($data);
                    });

                    Notification::make()
                        ->title('Berhasil Transfer Saldo')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->dataAwalTransaksi($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn ($livewire): array => $this->dataAwalTransaksi($livewire))
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
                    $dompet = Dompet::findOrFail($data['dompet_id']);
                    abort_unless(
                        auth()->user()->dapatMengelolaTransaksiPada($bukuKas)
                        && auth()->user()->dapatMengelolaTransaksiPadaDompet($dompet),
                        403
                    );

                    $data['jenis'] = 'Pemasukan';
                    $data['user_id'] = auth()->user()->id;
                    Transaksi::create($data);

                    Notification::make()
                        ->title('Berhasil Catat Pemasukan')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->dataAwalTransaksi($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn ($livewire): array => $this->dataAwalTransaksi($livewire))
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
                    $dompet = Dompet::findOrFail($data['dompet_id']);
                    abort_unless(
                        auth()->user()->dapatMengelolaTransaksiPada($bukuKas)
                        && auth()->user()->dapatMengelolaTransaksiPadaDompet($dompet),
                        403
                    );

                    $data['jenis'] = 'Pengeluaran';
                    $data['user_id'] = auth()->user()->id;

                    Transaksi::create($data);
                    Notification::make()
                        ->title('Berhasil Catat Pengeluaran')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->dataAwalTransaksi($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn (): array => [
                    'buku_kas_id' => $this->filterBukuKas ?: optional(BukuKas::first())->id,
                    'dompet_id' => $this->filterDompet ?: auth()->user()->idDompetUtama(),
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
        return filled($this->filterBukuKas) || filled($this->filterDompet) ? [KasOverview::class] : [];
    }
}
