<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\BukuKasResource\Pages;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\Kategori;
use App\Services\BukuKasService;
use App\Services\HargaEmasService;
use App\Services\OpsiSelectCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

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
        return $schema
            ->schema([
                // Forms\Components\TextInput::make('user_id')
                //     ->required()
                //     ->numeric(),

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

                // Forms\Components\TextInput::make('goal')
                //     ->numeric()
                //     ->default(null),
                // Forms\Components\DatePicker::make('tanggal_goal'),

                TextInput::make('description')
                    ->maxLength(200)
                    ->default(null),

                Toggle::make('hubungkan_kategori')
                    ->label('Pakai semua kategori saya di kas ini')
                    ->helperText('Matikan jika kas ini memerlukan daftar kategori sendiri, misalnya untuk kas bersama. Hubungan kategori dapat diubah di Setting > Kategori.')
                    ->default(true)
                    ->visible(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])
            ->columns([
                // Tables\Columns\TextColumn::make('user_id')
                //     ->numeric()
                //     ->sortable(),

                Stack::make([
                    TextColumn::make('nama_buku')
                        ->label('Kas')
                        ->searchable(['nama_buku', 'description'])
                        ->description(fn (BukuKas $record): ?string => filled($record->description) ? 'Deskripsi: '.$record->description : null)
                        ->weight('bold')
                        ->size('lg')
                        ->wrap(),

                    TextColumn::make('akses')
                        ->badge()
                        ->prefix('Akses: ')
                        ->state(fn (BukuKas $record): string => ucfirst(auth()->user()->hakAksesPada($record) ?? 'Tidak ada'))
                        ->description(fn (BukuKas $record): string => $record->user_id === auth()->id()
                            ? 'Kepemilikan: Milik saya'
                            : 'Kepemilikan: Dibagikan kepada saya'),

                    TextColumn::make('saldo')
                        ->prefix('Saldo: Rp ')
                        ->numeric()
                        ->sortable()
                        ->counts(['transaksi', 'tabunganEmas'])
                        ->description(fn (BukuKas $record): string => 'Jumlah transaksi: '.number_format($record->transaksi_count, 0, ',', '.')
                            .($record->tabungan_emas_count > 0
                                ? ' · Produk emas: '.number_format($record->tabungan_emas_count, 0, ',', '.')
                                : '')),

                    // Tables\Columns\TextColumn::make('goal')
                    //     ->numeric()
                    //     ->sortable(),
                    // Tables\Columns\TextColumn::make('tanggal_goal')
                    //     ->date()
                    //     ->sortable(),

                    TextColumn::make('created_at')
                        ->prefix('Dibuat: ')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('updated_at')
                        ->prefix('Diperbarui: ')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->space(3),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (BukuKas $record): bool => $record->user_id === auth()->id()),

                    FilamentAction::make('kolaborator')
                        ->label('Kolaborator Kas')
                        ->icon('heroicon-o-user-group')
                        ->url(fn (): string => ShareBukuResource::getUrl())
                        ->visible(fn (BukuKas $record): bool => $record->user_id === auth()->id()),

                    FilamentAction::make('cekNilaiEmas')
                        ->label('Cek Nilai Emas')
                        ->icon('heroicon-o-calculator')
                        ->visible(fn (BukuKas $record): bool => $record->tabunganEmas()->where('berat_gram', '>', 0)->exists())
                        ->modalHeading(fn (BukuKas $record): string => 'Nilai emas pada '.$record->nama_buku)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (BukuKas $record) => view('filament.valuasi-emas', ['record' => $record])),

                    FilamentAction::make('hargaEmasManual')
                        ->label('Harga Emas Manual')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (BukuKas $record): bool => $record->tabunganEmas()->exists()
                            && auth()->user()->dapatMengelolaTransaksiPada($record))
                        ->form([
                            TextInput::make('harga_per_gram')
                                ->label('Harga buyback per gram')
                                ->prefix('Rp')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ])
                        ->action(fn (BukuKas $record, array $data) => app(HargaEmasService::class)->simpanManual(
                            $record,
                            auth()->user(),
                            (int) $data['harga_per_gram'],
                        ))
                        ->successNotificationTitle('Harga emas manual berhasil disimpan untuk kas ini'),

                    DeleteAction::make()
                        ->visible(fn (BukuKas $record): bool => $record->user_id === auth()->id()
                            && ! $record->transaksi()->exists()
                            && ! $record->tabunganEmas()->exists()),

                    Action::make('hapusDanPindahkan')
                        ->visible(fn (BukuKas $record): bool => $record->user_id === auth()->id()
                            && ($record->transaksi()->exists() || $record->tabunganEmas()->exists()))
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->label('Hapus')
                        ->modalHeading('Pindahkan transaksi dan hapus kas')
                        ->modalDescription(fn (BukuKas $record): string => "Kas {$record->nama_buku} masih memiliki transaksi. Pilih kas tujuan sebelum menghapusnya.")
                        ->modalSubmitActionLabel('Pindahkan dan hapus')
                        ->form(fn (BukuKas $record): array => [
                            Select::make('buku_kas_id')
                                ->label('Kas tujuan')
                                ->options(fn (): array => array_filter(
                                    OpsiSelectCache::ingat('buku-kas', fn (): array => BukuKas::query()
                                        ->where('user_id', $record->user_id)
                                        ->pluck('nama_buku', 'id')
                                        ->all(), $record->user_id),
                                    fn ($id): bool => (int) $id !== (int) $record->id,
                                    ARRAY_FILTER_USE_KEY,
                                ))
                                ->helperText('Semua transaksi, saldo rupiah, dan tabungan emas akan digabungkan ke kas tujuan.')
                                ->searchable(false)
                                ->live()
                                ->rules([
                                    Rule::exists('buku_kas', 'id')
                                        ->where('user_id', $record->user_id)
                                        ->whereNot('id', $record->id),
                                ])
                                ->required(),

                            Section::make('Kategori transaksi')
                                ->description('Kategori berikut belum terhubung ke kas tujuan. Pilih kategori penggantinya di kas tujuan, atau biarkan kosong agar transaksinya menjadi tanpa kategori.')
                                ->schema(fn (Get $get): array => static::fieldPemetaanKategori($record, $get('buku_kas_id')))
                                ->visible(fn (Get $get): bool => static::fieldPemetaanKategori($record, $get('buku_kas_id')) !== []),
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
                ])->label('Aksi'),
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
            ? BukuKas::query()->where('user_id', $asal->user_id)->whereKeyNot($asal->id)->find($idKasTujuan)
            : null;

        if (! $tujuan) {
            return [];
        }

        $opsi = $tujuan->kategori()->withoutGlobalScopes()->orderBy('nama')->get()
            ->mapWithKeys(fn (Kategori $kategori): array => [$kategori->id => $kategori->nama.' ('.$kategori->tipe.')'])
            ->all();

        return collect(app(BukuKasService::class)->kategoriPerluDipetakan($asal, $tujuan))
            ->map(fn (string $nama, int $id): Select => Select::make('pemetaan_kategori.'.$id)
                ->label($nama)
                ->placeholder('Kosongkan kategori')
                ->options($opsi))
            ->values()
            ->all();
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
