<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Forms\BukuKasFormSchema;
use App\Filament\Forms\KomponenNominal;
use App\Filament\Forms\SelectKasDanSumberDana;
use App\Filament\Resources\BukuKasResource\Pages;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Services\BukuKasService;
use App\Services\HargaEmasService;
use App\Services\KategoriService;
use App\Services\OpsiSelectCache;
use App\Services\PengaturanTabunganEmas;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BukuKasResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $cluster = Pengaturan::class;

    protected static ?string $model = BukuKas::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kas';

    protected static ?string $pluralModelLabel = 'Kas';

    protected static ?string $navigationLabel = 'Kas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(BukuKasFormSchema::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('user:id,name,masa_aktif')->urutanListKas())
            ->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    TextColumn::make('nama_buku')
                        ->label('Kas')
                        ->searchable(['nama_buku', 'description'])
                        ->description(fn(BukuKas $record): ?string => filled($record->description)
                            ? 'Deskripsi: ' . $record->description
                            : null)
                        ->weight('bold')
                        ->size('lg')
                        ->wrap(),

                    // Tipe + status + akses digabung jadi satu baris badge
                    TextColumn::make('badges')
                        ->label('Info')
                        ->badge()
                        ->state(fn(BukuKas $record): array => [
                            $record->is_tabungan ? 'Kas Tabungan' : 'Kas Biasa',
                            'Status: ' . ($record->is_active ? 'Aktif' : 'Nonaktif'),
                            'Akses: ' . ucfirst(auth()->user()->hakAksesPada($record) ?? 'Tidak ada'),
                        ])
                        ->color(fn(string $state): string => match (true) {
                            $state === 'Kas Tabungan'            => 'warning',
                            $state === 'Status: Aktif'           => 'success',
                            str_starts_with($state, 'Akses:')    => 'primary',
                            default                              => 'gray',
                        })
                        ->description(fn(BukuKas $record): string => $record->user_id === auth()->id()
                            ? 'Kepemilikan: Milik saya'
                            : 'Kepemilikan: '.($record->user?->name ?? 'Tidak diketahui')),

                    TextColumn::make('saldo')
                        ->prefix('Saldo: Rp ')
                        ->numeric()
                        ->sortable()
                        ->color(fn(int $state): string => match (true) {
                            $state > 0 => 'success',
                            $state < 0 => 'danger',
                            default    => 'gray',
                        })
                        ->counts(['transaksi', 'tabunganEmas'])
                        ->description(fn(BukuKas $record): string => 'Jumlah transaksi: ' . number_format($record->transaksi_count, 0, ',', '.')
                            . (app(PengaturanTabunganEmas::class)->aktif() && $record->tabungan_emas_count > 0
                                ? ' · Produk emas: ' . number_format($record->tabungan_emas_count, 0, ',', '.')
                                : '')),

                    ViewColumn::make('progres_tabungan')
                        ->view('filament.tables.columns.progres-tabungan'),

                    TextColumn::make('created_at')
                        ->prefix('Dibuat: ')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->prefix('Diperbarui: ')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(),
                ])->space(1), // sebelumnya 3, ini yang paling banyak memangkas tinggi kartu
            ])
            ->filters([
                SelectFilter::make('kepemilikan')
                    ->label('Kepemilikan kas')
                    ->options([
                        'milik' => 'Milik sendiri',
                        'kolaborasi' => 'Kas kolaborasi',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'milik' => $query->where($query->qualifyColumn('user_id'), auth()->id()),
                            'kolaborasi' => $query->where($query->qualifyColumn('user_id'), '!=', auth()->id()),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn(BukuKas $record): bool => $record->is_active && $record->user_id === auth()->id())
                    ->before(fn(BukuKas $record) => abort_unless($record->is_active && $record->user_id === auth()->id(), 403))
                    ->mutateRecordDataUsing(function (BukuKas $record, array $data): array {
                        $opsi = BukuKasFormSchema::opsiKategori($record);
                        $terpilih = $record->idKategori();
                        $data['kategori'] = $terpilih;
                        $data['aktifkan_di_semua_kategori'] = ! empty($opsi) && count(array_intersect(array_keys($opsi), $terpilih)) === count($opsi);

                        return $data;
                    })
                    ->using(function (BukuKas $record, array $data): BukuKas {
                        return DB::transaction(function () use ($record, $data): BukuKas {
                            $kas = BukuKas::withoutGlobalScopes()
                                ->where('user_id', auth()->id())
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            if (! $kas->is_active) {
                                throw ValidationException::withMessages(['buku_kas' => 'Kas nonaktif tidak dapat diubah.']);
                            }

                            $kategoriDipilih = array_key_exists('kategori', $data) ? (array) $data['kategori'] : null;
                            unset($data['kategori'], $data['aktifkan_di_semua_kategori'], $data['hubungkan_kategori']);
                            $kas->update($data);

                            if ($kategoriDipilih !== null) {
                                app(KategoriService::class)->sinkronkanKasDenganKategori(auth()->user(), $kas, $kategoriDipilih);
                            }

                            return $kas;
                        });
                    }),

                FilamentAction::make('jadikanDefault')
                    ->label('Jadikan kas utama')
                    ->icon('heroicon-o-star')
                    ->visible(fn(BukuKas $record): bool => $record->is_active
                        && ! $record->is_default
                        && $record->user_id === auth()->id())
                    ->action(function (BukuKas $record): void {
                        static::jadikanKasUtama($record);
                    }),

                FilamentAction::make('nonaktifkan')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-pause-circle')
                    ->color('gray')
                    ->visible(fn(BukuKas $record): bool => $record->is_active
                        && ! $record->is_default
                        && $record->user_id === auth()->id()
                        && (int) $record->saldo === 0
                        && ! $record->tabunganEmas()->exists()
                        && auth()->user()->buku_kas()->aktif()->count() > 1)
                    ->action(function (BukuKas $record): void {
                        static::ubahStatusAktif($record, false);
                    }),

                FilamentAction::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn(BukuKas $record): bool => ! $record->is_active
                        && $record->user_id === auth()->id())
                    ->action(function (BukuKas $record): void {
                        static::ubahStatusAktif($record, true);
                    }),

                FilamentAction::make('kolaborator')
                    ->label('Kolaborator Kas')
                    ->icon('heroicon-o-user-group')
                    ->url(fn(): string => ShareBukuResource::getUrl())
                    ->visible(fn(BukuKas $record): bool => $record->is_active && $record->user_id === auth()->id()),

                FilamentAction::make('cekNilaiEmas')
                    ->label('Cek Nilai Emas')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn(BukuKas $record): bool => app(PengaturanTabunganEmas::class)->aktif()
                        && $record->is_active
                        && $record->tabunganEmas()->where('berat_gram', '>', 0)->exists())
                    ->modalHeading(fn(BukuKas $record): string => 'Nilai emas pada ' . $record->nama_buku)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn(BukuKas $record) => view('filament.valuasi-emas', ['record' => $record])),

                FilamentAction::make('hargaEmasManual')
                    ->label('Harga Emas Manual')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(BukuKas $record): bool => app(PengaturanTabunganEmas::class)->aktif()
                        && $record->is_active
                        && $record->tabunganEmas()->exists()
                        && auth()->user()->dapatMengelolaTransaksiPada($record))
                    ->form([
                        KomponenNominal::terapkan(
                            TextInput::make('harga_per_gram')
                                ->label('Harga buyback per gram')
                                ->minValue(1)
                                ->required()
                                ->placeholder('Contoh: 1.150.000')
                        ),
                    ])
                    ->action(fn(BukuKas $record, array $data) => app(HargaEmasService::class)->simpanManual(
                        $record,
                        auth()->user(),
                        (int) $data['harga_per_gram'],
                    ))
                    ->successNotificationTitle('Harga emas manual berhasil disimpan untuk kas ini'),

                DeleteAction::make()
                    ->visible(fn(BukuKas $record): bool => $record->is_active
                        && $record->user_id === auth()->id()
                        && ! $record->transaksi()->exists()
                        && ! $record->tabunganEmas()->exists())
                    ->before(fn(BukuKas $record) => abort_unless($record->is_active && $record->user_id === auth()->id(), 403)),

                Action::make('hapusDanPindahkan')
                    ->visible(fn(BukuKas $record): bool => $record->user_id === auth()->id()
                        && $record->is_active
                        && ($record->transaksi()->exists() || $record->tabunganEmas()->exists()))
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->label('Hapus')
                    ->modalHeading('Pindahkan transaksi dan hapus kas')
                    ->modalWidth('md')
                    ->modalDescription(fn(BukuKas $record): string => "Kas {$record->nama_buku} masih memiliki transaksi. Pilih kas tujuan sebelum menghapusnya.")
                    ->modalSubmitActionLabel('Pindahkan dan hapus')
                    ->form(fn(BukuKas $record): array => [
                        SelectKasDanSumberDana::terapkan(
                            Select::make('buku_kas_id')
                                ->label('Kas tujuan')
                                ->options(fn(): array => array_filter(
                                    Transaksi::opsiBukuKasMilikSendiri($record->user_id),
                                    fn($id): bool => (int) $id !== (int) $record->id,
                                    ARRAY_FILTER_USE_KEY,
                                ))
                                ->helperText('Semua transaksi, saldo rupiah, dan tabungan emas akan digabungkan ke kas tujuan.')
                                ->live()
                                ->rules([
                                    Rule::exists('buku_kas', 'id')
                                        ->where('user_id', $record->user_id)
                                        ->whereNot('id', $record->id),
                                ])
                                ->required()
                        ),

                        Section::make('Kategori transaksi')
                            ->description('Kategori berikut belum terhubung ke kas tujuan. Pilih kategori penggantinya di kas tujuan, atau biarkan kosong agar transaksinya menjadi tanpa kategori.')
                            ->schema(fn(Get $get): array => static::fieldPemetaanKategori($record, $get('buku_kas_id')))
                            ->visible(fn(Get $get): bool => static::fieldPemetaanKategori($record, $get('buku_kas_id')) !== []),
                    ])
                    ->action(function (BukuKas $record, array $data): void {
                        app(BukuKasService::class)->pindahkanDanHapus(
                            auth()->user(),
                            $record,
                            BukuKas::query()->where('user_id', $record->user_id)->findOrFail($data['buku_kas_id']),
                            $data['pemetaan_kategori'] ?? [],
                        );
                    })
                    ->successNotificationTitle('Transaksi dipindahkan dan kas berhasil dihapus'),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    /**
     * Satu pilihan kategori pengganti untuk setiap kategori kas asal yang belum terhubung ke kas tujuan.
     *
     * @return array<int, Select>
     */
    public static function fieldPemetaanKategori(BukuKas $asal, mixed $idKasTujuan): array
    {
        $tujuan = filled($idKasTujuan)
            ? BukuKas::query()->where('user_id', $asal->user_id)->aktif()->whereKeyNot($asal->id)->find($idKasTujuan)
            : null;

        if (! $tujuan) {
            return [];
        }

        $opsi = OpsiSelectCache::ingat('kategori', fn(): array => $tujuan->kategori()->withoutGlobalScopes()
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn(Kategori $kategori): array => [$kategori->id => $kategori->nama . ' (' . $kategori->tipe . ')'])
            ->all(), $tujuan->id, 'dengan-tipe');

        return collect(app(BukuKasService::class)->kategoriPerluDipetakan($asal, $tujuan))
            ->map(fn(string $nama, int $id): Select => Select::make('pemetaan_kategori.' . $id)
                ->label($nama)
                ->placeholder('Kosongkan kategori')
                ->options($opsi))
            ->values()
            ->all();
    }

    private static function jadikanKasUtama(BukuKas $record): void
    {
        DB::transaction(function () use ($record): void {
            $semuaKas = BukuKas::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $kas = $semuaKas->firstWhere('id', $record->id);

            if (! $kas || ! $kas->is_active) {
                throw ValidationException::withMessages(['buku_kas' => 'Kas tidak aktif atau tidak dapat dikelola.']);
            }

            BukuKas::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->update(['is_default' => false]);
            $kas->update(['is_default' => true]);
        });
    }

    private static function ubahStatusAktif(BukuKas $record, bool $aktif): void
    {
        DB::transaction(function () use ($record, $aktif): void {
            $semuaKas = BukuKas::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $kas = $semuaKas->firstWhere('id', $record->id);

            if (! $kas) {
                throw ValidationException::withMessages(['buku_kas' => 'Kas tidak dapat dikelola.']);
            }

            if ($aktif) {
                if ($kas->is_active) {
                    throw ValidationException::withMessages(['buku_kas' => 'Kas sudah aktif.']);
                }

                $kas->update(['is_active' => true]);

                return;
            }

            $jumlahAktif = $semuaKas->where('is_active', true)->count();

            if (
                ! $kas->is_active
                || $kas->is_default
                || (int) $kas->saldo !== 0
                || $kas->tabunganEmas()->exists()
                || $jumlahAktif <= 1
            ) {
                throw ValidationException::withMessages(['buku_kas' => 'Hanya kas non-utama bersaldo Rp0 tanpa produk emas yang dapat dinonaktifkan.']);
            }

            $kas->update(['is_active' => false]);
        });

        Notification::make()
            ->title($aktif ? 'Kas diaktifkan' : 'Kas dinonaktifkan')
            ->success()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBukuKas::route('/'),
            // 'create' => Pages\CreateBukuKas::route('/create'),
            // 'edit' => Pages\EditBukuKas::route('/{record}/edit'),
        ];
    }
}
