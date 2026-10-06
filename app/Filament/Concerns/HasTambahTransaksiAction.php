<?php

namespace App\Filament\Concerns;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Services\KategoriService;
use App\Services\TransaksiService;
use App\Services\TransferDompetService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\Rule;

trait HasTambahTransaksiAction
{
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

    /** @return array<int, string> */
    public static function opsiKategoriForm(Get $get): array
    {
        return Transaksi::opsiKategori((int) $get('buku_kas_id'), match ($get('jenis_form')) {
            'pemasukan' => 'Pemasukan',
            'pengeluaran' => 'Pengeluaran',
            default => null,
        });
    }

    private function dapatMenambahKasDompetDariForm(): bool
    {
        $user = auth()->user();

        return ! $user->isAdmin() && $user->masaAktifBerlaku();
    }

    private function buatKasDariForm(array $data): int
    {
        $user = auth()->user();
        abort_unless($this->dapatMenambahKasDompetDariForm() && $user->dapatMembuatBukuKas(), 403);

        $bukuKas = BukuKas::create([
            'user_id' => $user->id,
            'nama_buku' => trim((string) $data['nama_buku']),
            'saldo' => 0,
            'description' => $data['description'] ?? null,
        ]);

        if ((bool) ($data['hubungkan_kategori'] ?? true)) {
            app(KategoriService::class)->hubungkanSemuaKategoriPemilik($bukuKas);
        }

        $dompet = Dompet::findOrFail($user->idDompetUtama());
        app(TransaksiService::class)->buatSaldoAwal(
            $user,
            $bukuKas,
            $dompet,
            (int) $data['saldo'],
            'Saldo pertama',
        );

        return $bukuKas->getKey();
    }

    private function buatDompetDariForm(array $data): int
    {
        $user = auth()->user();
        abort_unless($this->dapatMenambahKasDompetDariForm() && $user->dapatMembuatDompet(), 403);

        return Dompet::create([
            'user_id' => $user->id,
            'nama_dompet' => trim((string) $data['nama_dompet']),
            'saldo' => 0,
            'is_default' => false,
            'description' => $data['description'] ?? null,
        ])->getKey();
    }

    private function buatKategoriDariForm(array $data): int
    {
        $bukuKasId = (int) ($this->mountedActions[0]['data']['buku_kas_id'] ?? 0);
        $bukuKas = BukuKas::findOrFail($bukuKasId);
        $user = auth()->user();
        abort_unless($user->can('create', Kategori::class), 403);
        abort_unless($user->dapatMengelolaKategoriPada($bukuKas), 403);
        $idKas = array_values(array_unique([
            ...($data['kas'] ?? []),
            $bukuKas->id,
        ]));

        return app(KategoriService::class)->buat(
            $user,
            ['nama' => $data['nama'], 'tipe' => $data['tipe']],
            $idKas,
        )->getKey();
    }

    /** @return array<int, string> */
    private function opsiKasKategoriUntukForm(): array
    {
        $bukuKas = BukuKas::find((int) ($this->mountedActions[0]['data']['buku_kas_id'] ?? 0));
        if (! $bukuKas) {
            return [];
        }

        $user = auth()->user();

        return BukuKas::query()->with('user:id,name')->get()
            ->filter(fn (BukuKas $kas): bool => $kas->user_id === $bukuKas->user_id
                && $user->dapatMengelolaKategoriPada($kas))
            ->mapWithKeys(fn (BukuKas $kas): array => [
                $kas->id => $kas->user_id === $user->id
                    ? $kas->nama_buku
                    : $kas->nama_buku.' (kas bersama '.($kas->user?->name ?? '-').')',
            ])
            ->all();
    }

    private function idKasAwalKategoriForm(): array
    {
        $opsiKas = $this->opsiKasKategoriUntukForm();
        $bukuKasId = (int) ($this->mountedActions[0]['data']['buku_kas_id'] ?? 0);

        return array_key_exists($bukuKasId, $opsiKas) ? [$bukuKasId] : [];
    }

    private function selectKasDenganOpsiTambah(Select $select): Select
    {
        return $select
            ->createOptionModalHeading('Tambah kas')
            ->createOptionForm([
                Grid::make(1)
                    ->schema([
                        TextInput::make('nama_buku')
                            ->required()
                            ->rules(fn (?BukuKas $record): array => [
                                Rule::unique('buku_kas', 'nama_buku')
                                    ->where('user_id', auth()->id())
                                    ->ignore($record?->id),
                            ])
                            ->maxLength(50),
                        TextInput::make('saldo')
                            ->prefix('Rp')
                            ->required()
                            ->numeric(),
                        TextInput::make('description')
                            ->maxLength(200)
                            ->default(null),
                        Toggle::make('hubungkan_kategori')
                            ->label('Pakai semua kategori saya di kas ini')
                            ->helperText('Matikan jika kas ini memerlukan daftar kategori sendiri, misalnya untuk kas bersama. Hubungan kategori dapat diubah di Setting > Kategori.')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->columnSpanFull(),
                    ]),
            ])
            ->createOptionUsing(fn (array $data): int => $this->buatKasDariForm($data))
            ->createOptionAction(fn (Action $action): Action => $action
                ->modalWidth('md')
                ->visible(fn (): bool => $this->dapatMenambahKasDompetDariForm()));
    }

    private function selectDompetDenganOpsiTambah(Select $select): Select
    {
        return $select
            ->createOptionModalHeading('Tambah dompet')
            ->createOptionForm([
                Grid::make(1)
                    ->schema([
                        TextInput::make('nama_dompet')
                            ->label('Nama dompet')
                            ->required()
                            ->maxLength(50)
                            ->rules(fn (): array => [
                                Rule::unique('dompet', 'nama_dompet')->where('user_id', auth()->id()),
                            ]),
                        TextInput::make('saldo')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),
            ])
            ->createOptionUsing(fn (array $data): int => $this->buatDompetDariForm($data))
            ->createOptionAction(fn (Action $action): Action => $action
                ->modalWidth('md')
                ->visible(fn (): bool => $this->dapatMenambahKasDompetDariForm()));
    }

    protected function buatAksiTambahTransaksi(): Action
    {
        return Action::make('Tambah transaksi')
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
                Grid::make(1)
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
                            ->columns(['default' => 2, 'sm' => 2])
                            ->gridDirection('row')
                            ->extraAttributes(['class' => '[&_.fi-btn]:w-full'])
                            ->required()
                            ->columnSpanFull(),
                        $this->selectKasDenganOpsiTambah(
                            Select::make('buku_kas_id')
                                ->label(fn (Get $get): string => $get('jenis_form') === 'transfer_dompet' ? 'Kas pencatatan' : 'Kas')
                                ->options(fn (Get $get): array => $get('jenis_form') === 'transfer_kas'
                                    ? $this->opsiBukuKasTransfer()
                                    : Transaksi::opsiBukuKasYangDapatDikelola()))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                // Kategori yang tidak terhubung ke kas baru dikosongkan.
                                if (! array_key_exists((int) $get('kategori_id'), static::opsiKategoriForm($get))) {
                                    $set('kategori_id', null);
                                }
                            })
                            ->hidden(fn (Get $get): bool => $get('jenis_form') === 'transfer_dompet'),
                        $this->selectDompetDenganOpsiTambah(
                            Select::make('dompet_id')
                                ->label(fn (Get $get): string => str_starts_with((string) $get('jenis_form'), 'transfer_') ? 'Dompet asal' : 'Dompet')
                                ->options(fn (Get $get): array => $get('jenis_form') === 'transfer_dompet'
                                    ? Transaksi::opsiDompetSumberTransfer()
                                    : Transaksi::opsiDompetYangDapatDikelola()))
                            ->required()
                            ->hidden(fn (Get $get): bool => $get('jenis_form') === 'transfer_kas'),
                        $this->selectKasDenganOpsiTambah(
                            Select::make('buku_kas_id_tujuan')
                                ->label('Kas tujuan')
                                ->options(fn (Get $get): array => array_filter(
                                    $this->opsiBukuKasTransfer(),
                                    fn ($id): bool => (int) $id !== (int) $get('buku_kas_id'),
                                    ARRAY_FILTER_USE_KEY,
                                )))
                            ->required()
                            ->visible(fn (Get $get): bool => $get('jenis_form') === 'transfer_kas'),
                        $this->selectDompetDenganOpsiTambah(
                            Select::make('dompet_id_tujuan')
                                ->label('Dompet tujuan')
                                ->options(fn (): array => Transaksi::opsiDompetYangDapatDikelola()))
                            ->different(fn (Get $get): string => $get('jenis_form') === 'transfer_dompet'
                                ? 'dompet_id'
                                : 'dompet_id_tidak_digunakan')
                            ->required()
                            ->visible(fn (Get $get): bool => $get('jenis_form') === 'transfer_dompet'),
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn (Get $get): array => static::opsiKategoriForm($get))
                            ->createOptionModalHeading('Tambah kategori')
                            ->createOptionForm([
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('nama')
                                            ->label('Nama kategori')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('tipe')
                                            ->label('Tipe kategori')
                                            ->options(array_combine(Kategori::TIPE, Kategori::TIPE))
                                            ->helperText('Semua berarti kategori ini muncul untuk pemasukan maupun pengeluaran.')
                                            ->default(fn (): string => auth()->user()->pisahkanTipeKategori() ? 'Pengeluaran' : 'Semua')
                                            ->required(),
                                        CheckboxList::make('kas')
                                            ->label('Dipakai di kas')
                                            ->options(fn (): array => $this->opsiKasKategoriUntukForm())
                                            ->helperText('Kategori hanya dapat dipilih pada transaksi kas yang dicentang. Semua kas harus milik pemilik yang sama.')
                                            ->default(fn (): array => $this->idKasAwalKategoriForm())
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->createOptionUsing(fn (array $data): int => $this->buatKategoriDariForm($data))
                            ->createOptionAction(fn (Action $action): Action => $action
                                ->modalWidth('md')
                                ->visible(fn (): bool => auth()->user()->can('create', Kategori::class)))
                            ->placeholder('Tanpa kategori')
                            ->visible(fn (Get $get): bool => in_array($get('jenis_form'), ['pemasukan', 'pengeluaran'], true)),
                        DateTimePicker::make('tanggal')->required()->seconds(false)->native(false)->maxDate(now()),
                        TextInput::make('nominal')->required()->numeric()->minValue(1)->prefix('Rp'),
                        TextInput::make('deskripsi')->columnSpanFull(),
                    ]),
            ])
            ->color(fn (Action $action): string => static::warnaFormTransaksi(
                $action->getFormData()['jenis_form'] ?? 'pemasukan',
            ))
            ->icon('heroicon-o-plus');
    }
}
