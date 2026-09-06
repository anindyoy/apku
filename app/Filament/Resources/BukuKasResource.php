<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\BukuKasResource\Pages;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Services\HargaEmasService;
use App\Services\OpsiSelectCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use UnitEnum;

class BukuKasResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = BukuKas::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('user_id')
                //     ->numeric()
                //     ->sortable(),

                TextColumn::make('nama_buku')
                    ->searchable()
                    ->description(fn (BukuKas $record): string => $record->user_id === auth()->id()
                        ? 'Milik saya'
                        : 'Dibagikan kepada saya'),

                TextColumn::make('akses')
                    ->badge()
                    ->state(fn (BukuKas $record): string => ucfirst(auth()->user()->hakAksesPada($record) ?? 'Tidak ada')),

                TextColumn::make('saldo')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('transaksi_count')
                    ->counts('transaksi')
                    ->label('Total Transaksi'),

                TextColumn::make('tabungan_emas_count')
                    ->counts('tabunganEmas')
                    ->label('Produk Emas'),

                // Tables\Columns\TextColumn::make('goal')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('tanggal_goal')
                //     ->date()
                //     ->sortable(),

                TextColumn::make('description')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
                            ->searchable()
                            ->rules([
                                Rule::exists('buku_kas', 'id')
                                    ->where('user_id', $record->user_id)
                                    ->whereNot('id', $record->id),
                            ])
                            ->required(),
                    ])
                    ->action(function (BukuKas $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $bukuKasAsal = BukuKas::query()
                                ->whereKey($record->id)
                                ->where('user_id', $record->user_id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            $bukuKasTujuan = BukuKas::query()
                                ->whereKey($data['buku_kas_id'])
                                ->whereKeyNot($bukuKasAsal->id)
                                ->where('user_id', $bukuKasAsal->user_id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            $bukuKasAsal->transaksi()->update([
                                'buku_kas_id' => $bukuKasTujuan->id,
                            ]);
                            $bukuKasAsal->tabunganEmas()->update([
                                'buku_kas_id' => $bukuKasTujuan->id,
                            ]);

                            $bukuKasTujuan->increment('saldo', $bukuKasAsal->saldo);
                            $bukuKasAsal->delete();
                        });
                    })
                    ->successNotificationTitle('Transaksi dipindahkan dan kas berhasil dihapus'),

            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
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
