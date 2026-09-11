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
