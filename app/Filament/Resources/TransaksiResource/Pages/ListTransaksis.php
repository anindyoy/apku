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
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
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

                Action::make('Pindah saldo dompet')
                    ->visible(fn (): bool => count(Transaksi::opsiDompetSumberTransfer()) >= 2
                        && (blank($this->filterBukuKas) || $this->bukuKasTerpilihMilikSendiri()))
                    ->schema([
                        Select::make('dompet_asal_id')
                            ->label('Dompet asal')
                            ->options(fn (): array => Transaksi::opsiDompetSumberTransfer())
                            ->default(fn (): ?int => $this->filterDompet ? (int) $this->filterDompet : auth()->user()->idDompetUtama())
                            ->required(),
                        Select::make('dompet_tujuan_id')
                            ->label('Dompet tujuan')
                            ->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola())
                            ->different('dompet_asal_id')
                            ->required(),
                        Select::make('buku_kas_id')
                            ->label('Kas pencatatan')
                            ->options(fn (): array => Transaksi::opsiBukuKasYangDapatDikelola())
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
            ])
                ->label('Aksi lainnya')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button(),

            Action::make('Transfer saldo')
                ->visible(fn (): bool => $this->dapatMengelolaBukuKasTerpilih() && $this->bukuKasTerpilihMilikSendiri())
                ->before(function (): void {
                    abort_unless($this->dapatMengelolaBukuKasTerpilih(), 403);
                })
                ->tooltip('Transfer saldo ke kas lain')
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    app(TransaksiService::class)->transferBukuKas(
                        auth()->user(),
                        BukuKas::findOrFail($data['buku_kas_id']),
                        BukuKas::findOrFail($data['buku_kas_id_tujuan']),
                        Dompet::findOrFail($data['dompet_id']),
                        Dompet::findOrFail($data['dompet_id_tujuan']),
                        (int) $data['nominal'],
                        $data['tanggal'],
                        $data['deskripsi'] ?? null,
                    );

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
                ->visible(fn (): bool => $this->memilikiBukuKasYangDapatDikelola())
                ->before(function (): void {
                    abort_unless($this->memilikiBukuKasYangDapatDikelola(), 403);
                })
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    app(TransaksiService::class)->buat(auth()->user(), $data, 'Pemasukan');

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
                ->visible(fn (): bool => $this->memilikiBukuKasYangDapatDikelola())
                ->before(function (): void {
                    abort_unless($this->memilikiBukuKasYangDapatDikelola(), 403);
                })
                ->action(function (?Transaksi $record, array $data, $livewire, $form, $action, array $arguments) {
                    app(TransaksiService::class)->buat(auth()->user(), $data, 'Pengeluaran');
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
