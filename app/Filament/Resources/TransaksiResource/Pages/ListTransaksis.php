<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\ImportTransaksiResource;
use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\ImportTransaksiService;
use App\Services\OpsiSelectCache;
use App\Services\TransaksiService;
use App\Services\TransferDompetService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListTransaksis extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransaksiResource::class;

    protected static ?string $navigationLabel = 'Transaksi';

    public $list_kas = [];

    public string $filterMonth = '';

    public string|int $filterYear = '';

    public ?string $filterBukuKas = null;

    public ?string $filterDompet = null;

    public function mount(?string $filterBukuKas = null): void
    {
        parent::mount();

        $this->filterMonth = request()->query('filter_month', date('m'));
        $this->filterYear = request()->query('filter_year', date('Y'));
        $requestedBukuKas = $filterBukuKas ?? request()->query('filter_buku_kas');
        $this->filterBukuKas = filled($requestedBukuKas) ? (string) $requestedBukuKas : null;
        $requestedDompet = request()->query('filter_dompet');
        $this->filterDompet = filled($requestedDompet) && Dompet::withTrashed()->whereKey($requestedDompet)->exists()
            ? (string) $requestedDompet
            : null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                View::make('filament.resources.transaksi-resource.pages.list-transaksis')
                    ->viewData(fn (): array => [
                        'filterMonth' => $this->filterMonth,
                        'filterYear' => $this->filterYear,
                        'filterBukuKas' => $this->filterBukuKas,
                        'filterDompet' => $this->filterDompet,
                    ]),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
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
        return OpsiSelectCache::ingat('buku-kas', fn (): array => BukuKas::orderByRaw("CASE WHEN nama_buku = 'Kas Utama' THEN 1 ELSE 2 END")
            ->orderBy('nama_buku')
            ->pluck('nama_buku', 'id')
            ->toArray(), auth()->id());
    }

    public function getDompetOptions(): array
    {
        return OpsiSelectCache::ingat('dompet', fn (): array => Dompet::withTrashed()
            ->orderByDesc('is_default')
            ->orderBy('nama_dompet')
            ->get()
            ->mapWithKeys(fn (Dompet $dompet): array => [
                $dompet->id => $dompet->nama_dompet.($dompet->trashed() ? ' (Dihapus)' : ''),
            ])
            ->all(), auth()->id(), 'dengan-terhapus');
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

    public function dataAwalFormTransaksi($livewire, string $jenisForm = 'pemasukan'): array
    {
        return [
            ...$this->dataAwalTransaksi($livewire),
            'jenis_form' => $jenisForm,
        ];
    }

    public static function warnaFormTransaksi(string $jenisForm): string
    {
        return match ($jenisForm) {
            'pemasukan' => 'success',
            'pengeluaran' => 'danger',
            'transfer_kas' => 'info',
            'transfer_dompet' => 'warning',
            default => 'gray',
        };
    }

    public function warnaFormTransaksiSaatIni(): string
    {
        $actionIndex = array_key_last($this->mountedActions ?? []);
        $jenisForm = $actionIndex === null
            ? 'pemasukan'
            : ($this->mountedActions[$actionIndex]['data']['jenis_form'] ?? 'pemasukan');

        return static::warnaFormTransaksi($jenisForm);
    }

    public static function targetLoadingPerubahanJenisForm(): string
    {
        return 'mountedActions.0.data.jenis_form';
    }

    public function dapatMengelolaBukuKasTerpilih(): bool
    {
        $bukuKas = BukuKas::find($this->filterBukuKas);

        return $bukuKas !== null
            && auth()->user()->dapatMengelolaTransaksiPada($bukuKas);
    }

    public function memilikiBukuKasYangDapatDikelola(): bool
    {
        return Transaksi::opsiBukuKasYangDapatDikelola() !== [];
    }

    public function opsiBukuKasTransfer(): array
    {
        $opsiBukuKas = Transaksi::opsiBukuKasYangDapatDikelola();
        $idBukuKasMilikSendiri = BukuKas::query()
            ->where('user_id', auth()->id())
            ->whereKey(array_keys($opsiBukuKas))
            ->pluck('id')
            ->all();

        return array_intersect_key($opsiBukuKas, array_flip($idBukuKasMilikSendiri));
    }

    public function idDompetTransferKas(): ?int
    {
        $opsiDompet = Transaksi::opsiDompetYangDapatDikelola();
        $dompetTerpilih = (int) $this->filterDompet;
        $dompetUtama = (int) auth()->user()->idDompetUtama();

        if (array_key_exists($dompetTerpilih, $opsiDompet)) {
            return $dompetTerpilih;
        }

        return array_key_exists($dompetUtama, $opsiDompet)
            ? $dompetUtama
            : array_key_first($opsiDompet);
    }

    public function idBukuKasTransferDompet(): ?int
    {
        $opsiBukuKas = $this->opsiBukuKasTransfer();
        $bukuKasTerpilih = (int) $this->filterBukuKas;
        $bukuKasUtama = (int) auth()->user()->idBukuKasUtama();

        if (array_key_exists($bukuKasTerpilih, $opsiBukuKas)) {
            return $bukuKasTerpilih;
        }

        return array_key_exists($bukuKasUtama, $opsiBukuKas)
            ? $bukuKasUtama
            : array_key_first($opsiBukuKas);
    }

    public function bukuKasTerpilihMilikSendiri(): bool
    {
        $bukuKas = BukuKas::find($this->filterBukuKas);

        return $bukuKas !== null && $bukuKas->user_id === auth()->id();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('Riwayat Import')
                    ->visible(fn (): bool => blank($this->filterBukuKas) || $this->bukuKasTerpilihMilikSendiri())
                    ->url(ImportTransaksiResource::getUrl())
                    ->color('gray')
                    ->icon('heroicon-o-clock'),

                Action::make('Unduh Template Import')
                    ->visible(fn (): bool => blank($this->filterBukuKas) || $this->bukuKasTerpilihMilikSendiri())
                    ->action(function () {
                        $path = tempnam(sys_get_temp_dir(), 'template-import-transaksi-');
                        app(ImportTransaksiService::class)->buatTemplateXlsx($path);

                        return response()->download($path, 'template-import-transaksi.xlsx')->deleteFileAfterSend(true);
                    })
                    ->color('gray')
                    ->icon('heroicon-o-arrow-down-tray'),

                Action::make('Import Transaksi')
                    ->visible(fn (): bool => blank($this->filterBukuKas) || $this->bukuKasTerpilihMilikSendiri())
                    ->schema([
                        FileUpload::make('file')
                            ->label('File CSV atau XLSX')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(10240)
                            ->storeFiles(false)
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $this->siapkanPemetaanImport($state, $set);
                            }),
                        Hidden::make('header_options'),
                        Select::make('pemetaan.tanggal')
                            ->label('Kolom tanggal')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.jenis')
                            ->label('Kolom jenis')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.buku_kas')
                            ->label('Kolom kas')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.dompet')
                            ->label('Kolom dompet')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.kategori')
                            ->label('Kolom aktivitas')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.nominal')
                            ->label('Kolom nominal')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Select::make('pemetaan.deskripsi')
                            ->label('Kolom deskripsi')
                            ->placeholder('Tidak dipetakan')
                            ->options(fn (Get $get): array => $get('header_options') ?? [])
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], (bool) $get('buat_kategori_otomatis'), $set)),
                        Toggle::make('buat_kategori_otomatis')
                            ->label('Buat aktivitas yang belum tersedia')
                            ->helperText('Aktivitas baru akan dibuat bersama transaksi setelah import dikonfirmasi.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(fn (bool $state, Get $get, Set $set): mixed => $this->perbaruiPratinjauImport($get('file'), $get('pemetaan') ?? [], $state, $set)),
                        Hidden::make('pratinjau'),
                        Placeholder::make('ringkasan_import')
                            ->label('Pratinjau')
                            ->content(fn (Get $get): HtmlString => $this->formatPratinjauImport($get('pratinjau'))),
                    ])
                    ->modalSubmitActionLabel('Import')
                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeModalSubmitAction('unduhLaporanError', arguments: ['unduh_laporan_error' => true])
                            ->label('Unduh laporan error')
                            ->color('gray')
                            ->icon('heroicon-o-document-arrow-down'),
                    ])
                    ->action(function (array $data, array $arguments) {
                        if (! ($data['file'] ?? null) instanceof TemporaryUploadedFile) {
                            throw ValidationException::withMessages(['file' => 'File import tidak tersedia. Silakan unggah ulang.']);
                        }

                        if ($arguments['unduh_laporan_error'] ?? false) {
                            $path = tempnam(sys_get_temp_dir(), 'laporan-error-import-');
                            app(ImportTransaksiService::class)->buatLaporanErrorXlsx(
                                auth()->user(), $data['file'], $path, pemetaan: $data['pemetaan'] ?? [],
                                buatKategoriOtomatis: (bool) ($data['buat_kategori_otomatis'] ?? false),
                            );

                            return response()->download($path, 'laporan-error-import-transaksi.xlsx')->deleteFileAfterSend(true);
                        }

                        $service = app(ImportTransaksiService::class);
                        $opsi = [
                            'pemetaan' => $data['pemetaan'] ?? [],
                            'buatKategoriOtomatis' => (bool) ($data['buat_kategori_otomatis'] ?? false),
                        ];
                        $pratinjau = $service->pratinjau(auth()->user(), $data['file'], ...$opsi);

                        if ($pratinjau['jumlah_baris'] > ImportTransaksiService::BATAS_BARIS_LANGSUNG) {
                            $service->antrekan(auth()->user(), $data['file'], ...$opsi);

                            Notification::make()
                                ->title($pratinjau['jumlah_baris'].' transaksi masuk antrean import')
                                ->body('Progres dapat dipantau pada Riwayat Import.')
                                ->info()
                                ->send();

                            return;
                        }

                        $hasil = $service->impor(auth()->user(), $data['file'], ...$opsi);

                        Notification::make()
                            ->title($hasil['jumlah_baris'].' transaksi berhasil diimpor')
                            ->body('Pemasukan Rp '.number_format($hasil['total_pemasukan'], 0, ',', '.').' · Pengeluaran Rp '.number_format($hasil['total_pengeluaran'], 0, ',', '.'))
                            ->success()
                            ->send();
                    })
                    ->color('info')
                    ->icon('heroicon-o-arrow-up-tray'),

            ])
                ->label('Aksi lainnya')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button(),

            Action::make('Tambah transaksi')
                ->label('Tambah transaksi')
                ->visible(fn (): bool => $this->memilikiBukuKasYangDapatDikelola())
                ->before(function (): void {
                    abort_unless($this->memilikiBukuKasYangDapatDikelola(), 403);
                })
                ->modalHeading('Tambah transaksi')
                ->action(function ($form, $action, $livewire, array $data, array $arguments): void {
                    $jenisForm = $data['jenis_form'];

                    if ($jenisForm === 'transfer_kas') {
                        abort_unless(count($this->opsiBukuKasTransfer()) >= 2, 403);
                    }

                    if ($jenisForm === 'transfer_dompet') {
                        abort_unless(
                            count(Transaksi::opsiDompetSumberTransfer()) >= 2
                                && filled($this->idBukuKasTransferDompet()),
                            403,
                        );
                    }

                    match ($jenisForm) {
                        'pemasukan' => app(TransaksiService::class)->buat(auth()->user(), $data, 'Pemasukan'),
                        'pengeluaran' => app(TransaksiService::class)->buat(auth()->user(), $data, 'Pengeluaran'),
                        'transfer_kas' => app(TransaksiService::class)->transferBukuKas(
                            auth()->user(),
                            BukuKas::findOrFail($data['buku_kas_id']),
                            BukuKas::findOrFail($data['buku_kas_id_tujuan']),
                            Dompet::findOrFail($this->idDompetTransferKas()),
                            Dompet::findOrFail($this->idDompetTransferKas()),
                            (int) $data['nominal'],
                            $data['tanggal'],
                            $data['deskripsi'] ?? null,
                        ),
                        'transfer_dompet' => app(TransferDompetService::class)->transfer(
                            auth()->user(),
                            Dompet::findOrFail($data['dompet_id']),
                            Dompet::findOrFail($data['dompet_id_tujuan']),
                            BukuKas::findOrFail($this->idBukuKasTransferDompet()),
                            (int) $data['nominal'],
                            $data['tanggal'],
                            $data['deskripsi'] ?? null,
                        ),
                    };

                    Notification::make()->title(match ($jenisForm) {
                        'pemasukan' => 'Berhasil Catat Pemasukan',
                        'pengeluaran' => 'Berhasil Catat Pengeluaran',
                        'transfer_kas' => 'Berhasil Transfer Saldo',
                        'transfer_dompet' => 'Saldo dompet berhasil dipindahkan',
                    })->success()->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->dataAwalFormTransaksi($livewire, $jenisForm));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn ($livewire): array => $this->dataAwalFormTransaksi($livewire))
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->color($this->warnaFormTransaksiSaatIni())
                    ->extraAttributes([
                        'wire:loading.attr' => 'disabled',
                        'wire:target' => static::targetLoadingPerubahanJenisForm(),
                    ]))
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain')
                        ->extraAttributes([
                            'wire:loading.attr' => 'disabled',
                            'wire:target' => static::targetLoadingPerubahanJenisForm(),
                        ]),
                ])
                ->form([
                    Grid::make(2)
                        ->extraAttributes([
                            'x-data' => '{ changingTransactionType: false }',
                            'x-on:change.capture' => <<<'JS'
                                if (! ['pemasukan', 'pengeluaran', 'transfer_kas', 'transfer_dompet'].includes($event.target.value)) return;
                                changingTransactionType = true;
                                const removeHook = $wire.$hook('commit', ({ succeed }) => succeed(() => queueMicrotask(() => {
                                    changingTransactionType = false;
                                    removeHook();
                                })));
                                JS,
                            'x-bind:inert' => 'changingTransactionType',
                            'x-bind:class' => "{ 'transaction-form-changing': changingTransactionType }",
                            'wire:loading.attr' => 'inert',
                            'wire:loading.class' => 'transaction-form-changing pointer-events-none',
                            'wire:target' => static::targetLoadingPerubahanJenisForm(),
                        ])
                        ->schema([
                            ToggleButtons::make('jenis_form')
                                ->label('Jenis transaksi')
                                ->options([
                                    'pemasukan' => 'Pemasukan',
                                    'pengeluaran' => 'Pengeluaran',
                                    'transfer_kas' => 'Transfer kas',
                                    'transfer_dompet' => 'Transfer dompet',
                                ])
                                ->afterStateUpdated(function (string $state, Set $set): void {
                                    if ($state === 'transfer_dompet') {
                                        $set('buku_kas_id', $this->idBukuKasTransferDompet());
                                        $set('buku_kas_id_tujuan', null);

                                        return;
                                    }

                                    if ($state !== 'transfer_kas') {
                                        return;
                                    }

                                    $opsiBukuKas = $this->opsiBukuKasTransfer();
                                    $bukuKasTerpilih = (int) $this->filterBukuKas;
                                    $set('buku_kas_id', array_key_exists($bukuKasTerpilih, $opsiBukuKas)
                                        ? $bukuKasTerpilih
                                        : array_key_first($opsiBukuKas));
                                    $set('buku_kas_id_tujuan', null);
                                    $set('dompet_id', $this->idDompetTransferKas());
                                    $set('dompet_id_tujuan', null);
                                })
                                ->colors([
                                    'pemasukan' => 'success',
                                    'pengeluaran' => 'danger',
                                    'transfer_kas' => 'info',
                                    'transfer_dompet' => 'warning',
                                ])
                                ->icons([
                                    'pemasukan' => 'heroicon-o-arrow-down-on-square',
                                    'pengeluaran' => 'heroicon-o-arrow-up-on-square',
                                    'transfer_kas' => 'heroicon-o-arrow-path-rounded-square',
                                    'transfer_dompet' => 'heroicon-o-arrows-right-left',
                                ])
                                ->disableOptionWhen(fn (string $value): bool => match ($value) {
                                    'transfer_kas' => count($this->opsiBukuKasTransfer()) < 2,
                                    'transfer_dompet' => count(Transaksi::opsiDompetSumberTransfer()) < 2
                                        || blank($this->idBukuKasTransferDompet()),
                                    default => false,
                                })
                                ->live()
                                ->grouped()
                                ->required()
                                ->columnSpanFull(),
                            Select::make('buku_kas_id')
                                ->label(fn (Get $get): string => $get('jenis_form') === 'transfer_dompet' ? 'Kas pencatatan' : 'Kas')
                                ->options(fn (Get $get): array => $get('jenis_form') === 'transfer_kas'
                                    ? $this->opsiBukuKasTransfer()
                                    : Transaksi::opsiBukuKasYangDapatDikelola())
                                ->required()
                                ->hidden(fn (Get $get): bool => $get('jenis_form') === 'transfer_dompet'),
                            Select::make('dompet_id')
                                ->label(fn (Get $get): string => str_starts_with((string) $get('jenis_form'), 'transfer_') ? 'Dompet asal' : 'Dompet')
                                ->options(fn (Get $get): array => $get('jenis_form') === 'transfer_dompet'
                                    ? Transaksi::opsiDompetSumberTransfer()
                                    : Transaksi::opsiDompetYangDapatDikelola())
                                ->required()
                                ->hidden(fn (Get $get): bool => $get('jenis_form') === 'transfer_kas'),
                            Select::make('buku_kas_id_tujuan')
                                ->label('Kas tujuan')
                                ->options(fn (Get $get): array => array_filter(
                                    $this->opsiBukuKasTransfer(),
                                    fn ($id): bool => (int) $id !== (int) $get('buku_kas_id'),
                                    ARRAY_FILTER_USE_KEY,
                                ))
                                ->required()
                                ->visible(fn (Get $get): bool => $get('jenis_form') === 'transfer_kas'),
                            Select::make('dompet_id_tujuan')
                                ->label('Dompet tujuan')
                                ->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola())
                                ->different(fn (Get $get): string => $get('jenis_form') === 'transfer_dompet'
                                    ? 'dompet_id'
                                    : 'dompet_id_tidak_digunakan')
                                ->required()
                                ->visible(fn (Get $get): bool => $get('jenis_form') === 'transfer_dompet'),
                            Select::make('jenis_transaksi_id')
                                ->label('Aktivitas')
                                ->options(fn (Get $get): array => Transaksi::opsiJenisTransaksi(match ($get('jenis_form')) {
                                    'pemasukan' => 'Pemasukan',
                                    'pengeluaran' => 'Pengeluaran',
                                    default => null,
                                }))
                                ->required()
                                ->visible(fn (Get $get): bool => in_array($get('jenis_form'), ['pemasukan', 'pengeluaran'], true)),
                            DateTimePicker::make('tanggal')->required()->seconds(false)->native(false)->maxDate(now()),
                            TextInput::make('nominal')->required()->numeric()->minValue(1)->prefix('Rp'),
                            TextInput::make('deskripsi')->columnSpanFull(),
                        ]),
                ])
                ->color(fn (Action $action): string => static::warnaFormTransaksi(
                    $action->getFormData()['jenis_form'] ?? 'pemasukan',
                ))
                ->icon('heroicon-o-plus'),
        ];
    }

    /** @param array<string, mixed>|null $pratinjau */
    public function formatPratinjauImport(?array $pratinjau): HtmlString
    {
        if ($pratinjau === null) {
            return new HtmlString('<p>Unggah file untuk melihat hasil validasi sebelum import.</p>');
        }

        $jumlah = number_format((int) ($pratinjau['jumlah_baris'] ?? 0), 0, ',', '.');
        $pemasukan = number_format((int) ($pratinjau['total_pemasukan'] ?? 0), 0, ',', '.');
        $pengeluaran = number_format((int) ($pratinjau['total_pengeluaran'] ?? 0), 0, ',', '.');
        $errors = $pratinjau['errors'] ?? [];
        $kategoriBaru = $pratinjau['kategori_baru'] ?? [];
        $html = '<div class="space-y-2"><p><strong>'.$jumlah.'</strong> baris · Pemasukan Rp '.$pemasukan.' · Pengeluaran Rp '.$pengeluaran.'</p>';

        if ($kategoriBaru !== []) {
            $html .= '<div class="text-warning-600"><strong>Aktivitas yang akan dibuat:</strong><ul class="list-disc pl-5 text-sm">';

            foreach ($kategoriBaru as $jenis => $daftar) {
                foreach ($daftar as $nama) {
                    $html .= '<li>'.e($jenis.': '.$nama).'</li>';
                }
            }

            $html .= '</ul></div>';
        }

        if ($errors === []) {
            $html .= '<p class="text-success-600">Semua baris valid dan siap diimpor.</p>';
        } else {
            $html .= '<p class="text-danger-600"><strong>'.count($errors).' masalah ditemukan:</strong></p><ul class="list-disc pl-5 text-sm text-danger-600">';

            foreach (array_slice($errors, 0, 20) as $error) {
                $html .= '<li>'.e($error).'</li>';
            }

            if (count($errors) > 20) {
                $html .= '<li>'.e('Masih ada '.(count($errors) - 20).' masalah lainnya.').'</li>';
            }

            $html .= '</ul>';
        }

        return new HtmlString($html.'</div>');
    }

    public function siapkanPemetaanImport(mixed $file, Set $set): void
    {
        if (! $file instanceof TemporaryUploadedFile) {
            $set('header_options', []);
            $set('pemetaan', []);
            $set('pratinjau', null);

            return;
        }

        try {
            $service = app(ImportTransaksiService::class);
            $header = $service->bacaHeader($file);
            $pemetaan = $service->sarankanPemetaan($header);
            $set('header_options', $header);
            $set('pemetaan', $pemetaan);
            $this->perbaruiPratinjauImport($file, $pemetaan, false, $set);
        } catch (ValidationException $exception) {
            $set('header_options', []);
            $set('pemetaan', []);
            $this->setErrorPratinjauImport($set, $exception);
        }
    }

    /** @param array<string, mixed> $pemetaan */
    public function perbaruiPratinjauImport(
        mixed $file,
        array $pemetaan,
        bool $buatKategoriOtomatis,
        Set $set,
    ): void {
        if (! $file instanceof TemporaryUploadedFile) {
            $set('pratinjau', null);

            return;
        }

        try {
            $hasil = app(ImportTransaksiService::class)->pratinjau(
                auth()->user(), $file, pemetaan: $pemetaan, buatKategoriOtomatis: $buatKategoriOtomatis,
            );
            $set('pratinjau', collect($hasil)->only([
                'jumlah_baris', 'total_pemasukan', 'total_pengeluaran', 'kategori_baru', 'errors',
            ])->all());
        } catch (ValidationException $exception) {
            $this->setErrorPratinjauImport($set, $exception);
        }
    }

    private function setErrorPratinjauImport(Set $set, ValidationException $exception): void
    {
        $set('pratinjau', [
            'jumlah_baris' => 0,
            'total_pemasukan' => 0,
            'total_pengeluaran' => 0,
            'errors' => collect($exception->errors())->flatten()->all(),
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return filled($this->filterBukuKas) || filled($this->filterDompet) ? [KasOverview::class] : [];
    }
}
