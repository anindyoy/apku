<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Forms\SelectKasDanSumberDana;
use App\Filament\Forms\SumberDanaFormSchema;
use App\Filament\Resources\SumberDanaResource\Pages\ListSumberDana;
use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Services\TransferDompetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Grid as TableGrid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SumberDanaResource extends Resource
{
    use HidesFromAdminNavigation;

    public static function shouldRegisterNavigation(): bool
    {
        // Sumber Dana tetap tampil di submenu Setting untuk pengguna non-admin.
        return ! auth()->user()?->isAdmin();
    }

    protected static ?string $cluster = Pengaturan::class;
    protected static ?string $modelLabel = 'Sumber Dana';

    protected static ?string $model = SumberDana::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Sumber Dana';

    protected static ?string $slug = 'sumber-dana';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(SumberDanaFormSchema::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->reorder()
                ->orderByDesc('is_active')
                ->orderByDesc('is_default')
                ->orderBy('nama_dompet'))
            ->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    TextColumn::make('nama_dompet')->label('Sumber dana')->searchable()->weight('bold')->size('lg')->wrap(),
                    TableGrid::make(['default' => 1, 'xl' => 2])
                        ->schema([
                            Stack::make([
                                TextColumn::make('jenis_label')->label('Jenis')->prefix('Jenis: '),
                                TextColumn::make('saldo')
                                    ->prefix('Saldo: Rp ')
                                    ->numeric()
                                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'gray'),
                            ])->space(3),
                            Stack::make([
                                TextColumn::make('is_default')
                                    ->label('Utama')
                                    ->prefix('Utama: ')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextColumn::make('status_akses')
                                    ->label('Status')
                                    ->prefix('Status: ')
                                    ->badge()
                                    ->getStateUsing(fn (SumberDana $record): string => auth()->user()->dapatMengelolaTransaksiPadaDompet($record) ? 'Aktif' : 'Terbatas')
                                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'danger'),
                                TextColumn::make('is_active')
                                    ->label('Kondisi')
                                    ->prefix('Kondisi: ')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextColumn::make('description')
                                    ->label('Deskripsi')
                                    ->prefix('Deskripsi: ')
                                    ->wrap()
                                    ->columnSpanFull(),
                            ])->space(3),
                        ]),
                ])->space(3),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (SumberDana $record): bool => auth()->user()->dapatMengelolaTransaksiPadaDompet($record))
                    ->before(fn (SumberDana $record) => abort_unless(auth()->user()->dapatMengelolaTransaksiPadaDompet($record), 403))
                    ->using(function (SumberDana $record, array $data): SumberDana {
                        return DB::transaction(function () use ($record, $data): SumberDana {
                            $sumberDana = SumberDana::withoutGlobalScopes()
                                ->where('user_id', auth()->id())
                                ->whereNull('deleted_at')
                                ->whereKey($record->id)
                                ->lockForUpdate()
                                ->firstOrFail();

                            if (! $sumberDana->is_active) {
                                throw ValidationException::withMessages(['sumber_dana' => 'Sumber dana nonaktif tidak dapat diubah.']);
                            }

                            $sumberDana->update($data);

                            return $sumberDana;
                        });
                    }),
                Action::make('jadikanDefault')
                    ->label('Jadikan utama')
                    ->icon('heroicon-o-star')
                    ->visible(fn (SumberDana $record): bool => ! $record->is_default && auth()->user()->dapatMengelolaTransaksiPadaDompet($record))
                    ->action(function (SumberDana $record): void {
                        static::jadikanUtama($record);
                    }),
                Action::make('nonaktifkan')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-pause-circle')
                    ->color('gray')
                    ->visible(fn (SumberDana $record): bool => $record->is_active
                        && ! $record->is_default
                        && $record->user_id === auth()->id()
                        && (int) $record->saldo === 0
                        && auth()->user()->sumberDana()->aktif()->count() > 1)
                    ->action(function (SumberDana $record): void {
                        static::ubahStatusAktif($record, false);
                    }),
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (SumberDana $record): bool => ! $record->is_active
                        && $record->user_id === auth()->id())
                    ->action(function (SumberDana $record): void {
                        static::ubahStatusAktif($record, true);
                    }),
                Action::make('pindahkanDanHapus')
                    ->label('Hapus')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->visible(fn (SumberDana $record): bool => ! $record->is_default
                        && $record->is_active
                        && auth()->user()->dapatMengelolaTransaksiPadaDompet($record)
                        && auth()->user()->sumberDana()->aktif()->count() > 1)
                    ->modalHeading(fn (SumberDana $record): string => (int) $record->saldo === 0 ? 'Hapus sumber dana' : 'Pindahkan saldo dan hapus sumber dana')
                    ->modalDescription(fn (SumberDana $record): string => (int) $record->saldo === 0
                        ? 'Sumber dana ini sudah memiliki saldo Rp0. Anda dapat menghapusnya tanpa memindahkan saldo.'
                        : 'Sumber dana ini masih memiliki saldo. Pilih sumber dana tujuan dan kas pencatatan untuk memindahkan saldo sebelum menghapusnya.')
                    ->form(fn (SumberDana $record): array => (int) $record->saldo === 0 ? [] : [
                        SelectKasDanSumberDana::terapkan(
                            Select::make('dompet_tujuan_id')
                                ->label('Sumber dana tujuan')
                                ->options(fn (): array => array_filter(
                                    Transaksi::opsiSumberDanaYangDapatDikelola(),
                                    fn ($id): bool => (int) $id !== (int) $record->id,
                                    ARRAY_FILTER_USE_KEY,
                                ))
                                ->required()
                        ),
                        SelectKasDanSumberDana::terapkan(
                            Select::make('buku_kas_id')
                                ->label('Kas pencatatan')
                                ->options(fn (): array => Transaksi::opsiBukuKasYangDapatDikelola())
                                ->default(fn (): ?int => auth()->user()->idBukuKasUtama())
                                ->required()
                        ),
                    ])
                    ->action(function (SumberDana $record, array $data = []): void {
                        if ((int) $record->saldo === 0) {
                            app(TransferDompetService::class)->hapusTanpaTransfer(auth()->user(), $record);

                            return;
                        }

                        app(TransferDompetService::class)->pindahkanSaldoDanHapus(
                            auth()->user(),
                            $record,
                            SumberDana::findOrFail($data['dompet_tujuan_id']),
                            BukuKas::findOrFail($data['buku_kas_id']),
                        );
                    }),
            ]);
    }

    private static function jadikanUtama(SumberDana $record): void
    {
        DB::transaction(function () use ($record): void {
            $sumberDanaSemua = SumberDana::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $sumberDana = $sumberDanaSemua->firstWhere('id', $record->id);

            if (! $sumberDana || ! $sumberDana->is_active) {
                throw ValidationException::withMessages(['sumber_dana' => 'Sumber dana tidak aktif atau tidak dapat dikelola.']);
            }

            SumberDana::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->update(['is_default' => false]);
            $sumberDana->update(['is_default' => true]);
        });
    }

    private static function ubahStatusAktif(SumberDana $record, bool $aktif): void
    {
        DB::transaction(function () use ($record, $aktif): void {
            $sumberDanaSemua = SumberDana::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $sumberDana = $sumberDanaSemua->firstWhere('id', $record->id);

            if (! $sumberDana) {
                throw ValidationException::withMessages(['sumber_dana' => 'Sumber dana tidak dapat dikelola.']);
            }

            if ($aktif) {
                if ($sumberDana->is_active) {
                    throw ValidationException::withMessages(['sumber_dana' => 'Sumber dana sudah aktif.']);
                }

                $sumberDana->update(['is_active' => true]);

                return;
            }

            $jumlahAktif = $sumberDanaSemua->where('is_active', true)->count();

            if (! $sumberDana->is_active || $sumberDana->is_default || (int) $sumberDana->saldo !== 0 || $jumlahAktif <= 1) {
                throw ValidationException::withMessages(['sumber_dana' => 'Hanya sumber dana non-utama dengan saldo Rp0 yang dapat dinonaktifkan.']);
            }

            $sumberDana->update(['is_active' => false]);
        });

        Notification::make()
            ->title($aktif ? 'Sumber dana diaktifkan' : 'Sumber dana dinonaktifkan')
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return ['index' => ListSumberDana::route('/')];
    }
}
