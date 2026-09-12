<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = Transaksi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $pluralLabel = 'Transaksi';

    protected static ?string $slug = 'transaksi';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(Transaksi::form());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query->select(
                    'transaksi.*',
                    DB::raw(
                        'SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) as saldo'
                    ),
                    DB::raw(
                        'SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY dompet_id ORDER BY tanggal, id desc) as saldo_dompet'
                    )
                )
            )
            ->searchPlaceholder('Cari deskripsi...')
            ->paginated([10, 25, 50])
            ->columns(static::transactionColumns())
            ->defaultSort('tanggal', 'desc')
            ->recordUrl(null)
            ->recordAction(null)
            ->filters([
            ])
            ->actions(static::transactionActions())
            ->bulkActions([
                // DeleteBulkAction::make(),
            ]);
    }

    public static function transactionActions(): array
    {
        return [
            Action::make('infoAkses')
                ->label(fn (Transaksi $record): string => static::alasanTransaksiTidakDapatDikelola($record)['label'] ?? 'Informasi akses')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->visible(fn (Transaksi $record): bool => static::alasanTransaksiTidakDapatDikelola($record) !== null)
                ->modalHeading(fn (Transaksi $record): string => static::alasanTransaksiTidakDapatDikelola($record)['label'] ?? 'Informasi akses')
                ->modalDescription(fn (Transaksi $record): string => static::alasanTransaksiTidakDapatDikelola($record)['description'] ?? '')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            Action::make('edit')
                ->label('Ubah')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->modalHeading('Ubah transaksi')
                ->modalSubmitActionLabel('Simpan')
                ->form(Transaksi::form())
                ->fillForm(fn (Transaksi $record): array => $record->attributesToArray())
                ->hidden(fn ($record): bool => auth()->user()->isAdmin()
                    || filled($record->audit_saldo_dompet_detail_id)
                    || $record->user_id !== auth()->id()
                    || ! auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas)
                    || ! auth()->user()->dapatMengelolaTransaksiPadaDompet($record->dompet))
                ->action(fn (Transaksi $record, array $data): Transaksi => app(TransaksiService::class)->ubah(auth()->user(), $record, $data)),

            DeleteAction::make()
                ->hidden(fn ($record): bool => auth()->user()->isAdmin()
                    || filled($record->audit_saldo_dompet_detail_id)
                    || $record->user_id !== auth()->id()
                    || ! auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas)
                    || ! auth()->user()->dapatMengelolaTransaksiPadaDompet($record->dompet))
                ->using(fn (Transaksi $record): bool => app(TransaksiService::class)->hapus(auth()->user(), $record)),
        ];
    }

    public static function alasanTransaksiTidakDapatDikelola(Transaksi $record): ?array
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return null;
        }
        if (filled($record->audit_saldo_dompet_detail_id)) {
            return ['label' => 'Transaksi audit saldo', 'description' => 'Transaksi penyesuaian audit saldo tidak dapat diubah atau dihapus langsung. Lakukan audit saldo baru untuk mengoreksi saldo.'];
        }
        if ($record->user_id !== $user->id) {
            return ['label' => 'Hanya dapat dilihat', 'description' => 'Transaksi ini dicatat oleh pengguna lain. Hanya pencatat yang memiliki hak pengelolaan kas dan dompet yang dapat mengubah atau menghapusnya.'];
        }
        if (! $record->buku_kas || ! $user->dapatMengelolaTransaksiPada($record->buku_kas)) {
            return $record->buku_kas?->user_id === $user->id
                ? ['label' => 'Kas tidak aktif', 'description' => 'Kas ini memiliki akses terbatas. Akun Reguler dapat mengelola kas utama dan satu kas tambahan gratis. Aktifkan atau perpanjang Premium untuk mengubah dan menghapus transaksi pada kas tambahan lainnya.']
                : ['label' => 'Akses kas terbatas', 'description' => 'Hak pengelolaan kas bersama tidak tersedia. Periksa masa akses dan peran Editor dengan pemilik kas, serta status akses kas milik pemiliknya.'];
        }
        if (! $record->dompet || ! $user->dapatMengelolaTransaksiPadaDompet($record->dompet)) {
            return ['label' => 'Dompet tidak aktif', 'description' => 'Dompet transaksi ini tidak dapat dikelola. Akun Reguler dapat mengelola dompet utama dan satu dompet tambahan gratis. Periksa kepemilikan dompet dan masa aktif Premium Anda.'];
        }

        return null;
    }

    public static function transactionColumns(): array
    {
        return [
            IconColumn::make('jenis')
                ->label('Tipe')
                ->tooltip(fn ($state) => $state)
                ->icon(fn (string $state): string => match ($state) {
                    'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                    'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                    'Transfer Pemasukan' => 'heroicon-o-arrow-path-rounded-square',
                    'Transfer Pengeluaran' => 'heroicon-o-arrow-path-rounded-square',
                })
                ->color(fn (Transaksi $record): string => static::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer)),

            TextColumn::make('tanggal')
                ->formatStateUsing(fn ($state) => date('d M Y, H:i', strtotime($state)))
                ->description(fn (Transaksi $record): string => 'Dicatat oleh: '.($record->user?->name ?? '-'))
                ->color(fn (Transaksi $record): string => static::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer)),

            TextColumn::make('buku_kas.nama_buku')
                ->label('Kas')
                ->description(fn (Transaksi $record): string => 'Dompet: '.$record->labelDompetUntuk(auth()->user()))
                ->color(fn (Transaksi $record): string => static::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer)),

            TextColumn::make('kategori')
                ->label('Aktivitas')
                ->getStateUsing(fn (Transaksi $record) => static::getKategoriLabel($record))
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query
                        ->where('deskripsi', 'like', "%{$search}%");
                })
                ->description(
                    fn ($record) => $record->deskripsi
                        ? ('Deskripsi: '.$record->deskripsi) : ''
                )
                ->color(fn (Transaksi $record): string => static::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer))
                ->wrap(),

            TextColumn::make('created_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('updated_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('nominal')
                ->numeric()
                ->prefix('Rp ')
                ->color(fn (Transaksi $record): string => static::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer))
                ->description(function (Transaksi $record, $livewire): ?string {
                    $saldo = [];

                    if (filled($livewire->filterBukuKas ?? null)) {
                        $saldo[] = 'Saldo kas: Rp '.number_format((float) $record->saldo, 0, ',', '.');
                    }

                    if (filled($livewire->filterDompet ?? null)) {
                        $saldo[] = 'Saldo dompet: Rp '.number_format((float) $record->saldo_dompet, 0, ',', '.');
                    }

                    return filled($saldo) ? implode(' · ', $saldo) : null;
                }),
        ];
    }

    public static function getWarnaTipeTransaksi(string $jenis, ?string $tipeTransfer = null): string
    {
        return match ($jenis) {
            'Pemasukan' => 'success',
            'Pengeluaran' => 'danger',
            'Transfer Pemasukan', 'Transfer Pengeluaran' => $tipeTransfer === 'dompet' ? 'warning' : 'info',
            default => 'gray',
        };
    }

    public static function getKategoriLabel(Transaksi $transaksi): ?string
    {
        $label = match ($transaksi->jenis) {
            'Transfer Pemasukan' => $transaksi->tipe_transfer === 'dompet'
                ? 'Transfer masuk dompet'
                : 'Transfer dari '.($transaksi->asal_buku_tabungan?->nama_buku ?? '-'),
            'Transfer Pengeluaran' => $transaksi->tipe_transfer === 'dompet'
                ? 'Transfer keluar dompet'
                : 'Transfer ke '.($transaksi->tujuan_buku_tabungan?->nama_buku ?? '-'),
            default => $transaksi->jenis_transaksi?->nama_jenis,
        };

        return filled($label) ? Str::ucfirst($label) : null;
    }

    public static function getWidgets(): array
    {
        return [
            KasOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransaksis::route('/'),
            // 'create' => Pages\CreateTransaksi::route('/create'),
            // 'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
