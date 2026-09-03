<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TransaksiResource extends Resource
{
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
            ->columns([
                IconColumn::make('jenis')
                    ->label('Tipe')
                    ->tooltip(fn ($state) => $state)
                    ->icon(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                        'Transfer Pemasukan' => 'heroicon-o-arrow-path-rounded-square',
                        'Transfer Pengeluaran' => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                        'Transfer Pemasukan' => 'primary',
                        'Transfer Pengeluaran' => 'primary',
                    }),

                TextColumn::make('user.name')
                    ->numeric()
                    ->visible(auth()->user()->isSuper()),

                TextColumn::make('tanggal')
                    ->formatStateUsing(fn ($state) => date('d M Y, H:i', strtotime($state))),

                TextColumn::make('buku_kas.nama_buku')
                    ->label('Buku Kas')
                    ->visible(fn (ListTransaksis $livewire): bool => blank($livewire->filterBukuKas)),

                TextColumn::make('dompet.nama_dompet')
                    ->label('Dompet')
                    ->visible(fn (ListTransaksis $livewire): bool => blank($livewire->filterDompet)),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->getStateUsing(fn (Transaksi $record) => static::getKategoriLabel($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('deskripsi', 'like', "%{$search}%");
                    })
                    ->description(
                        fn ($record) => $record->deskripsi
                            ? ('Deskripsi: '.$record->deskripsi) : ''
                    )
                    ->wrap(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nominal')
                    ->numeric()
                    ->prefix('Rp '),

                TextColumn::make('saldo')->numeric()
                    ->prefix('Rp ')
                    ->visible(fn (ListTransaksis $livewire): bool => filled($livewire->filterBukuKas)),

                TextColumn::make('saldo_dompet')
                    ->label('Saldo Dompet')
                    ->numeric()
                    ->prefix('Rp ')
                    ->color(fn ($state): string => (int) $state < 0 ? 'danger' : 'gray')
                    ->tooltip(fn ($state): ?string => (int) $state < 0 ? 'Saldo dompet negatif' : null)
                    ->visible(fn (ListTransaksis $livewire): bool => filled($livewire->filterDompet)),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
            ])
            ->actions([
                EditAction::make()
                    ->hidden(fn ($record): bool => auth()->user()->isSuper()
                        || ! auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas)
                        || ! auth()->user()->dapatMengelolaTransaksiPadaDompet($record->dompet))
                    ->before(function ($record, $livewire) {
                        abort_unless(
                            auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas)
                            && auth()->user()->dapatMengelolaTransaksiPadaDompet($record->dompet),
                            403
                        );

                        $bukuKasBaru = BukuKas::findOrFail(
                            $livewire->mountedTableActionsData[0]['buku_kas_id']
                        );
                        abort_unless(auth()->user()->dapatMengelolaTransaksiPada($bukuKasBaru), 403);

                        $dompetBaru = Dompet::findOrFail(
                            $livewire->mountedTableActionsData[0]['dompet_id']
                        );
                        abort_unless(auth()->user()->dapatMengelolaTransaksiPadaDompet($dompetBaru), 403);

                        if (in_array($record->jenis, ['Transfer Pemasukan', 'Transfer Pengeluaran'])) {
                            $relatedTransactions = Transaksi::where('transfer_code', $record->transfer_code)->get();
                            $nominal_baru = $livewire->mountedTableActionsData[0]['nominal'];
                            $selisih = $nominal_baru - $record->nominal;

                            foreach ($relatedTransactions as $relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo -= $selisih;
                                } elseif ($relatedTransaction->jenis === 'Transfer Pemasukan') {
                                    $relatedKas->saldo += $selisih;
                                }

                                $relatedKas->save();

                                if ($relatedTransaction->id != $record->id) {
                                    $relatedTransaction->nominal = $nominal_baru;
                                    $relatedTransaction->save();
                                }
                            }
                        }
                    }),

                DeleteAction::make()
                    ->hidden(fn ($record): bool => auth()->user()->isSuper()
                        || ! auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas)
                        || ! auth()->user()->dapatMengelolaTransaksiPadaDompet($record->dompet))
                    ->before(function ($record): void {
                        abort_unless(auth()->user()->dapatMengelolaTransaksiPada($record->buku_kas), 403);
                    })
                    ->after(function ($record) {
                        $kas = $record->buku_kas;
                        if (in_array($record->jenis, ['Transfer Pengeluaran', 'Transfer Pemasukan'])) {
                            $kas = $record->buku_kas;

                            if ($record->jenis === 'Transfer Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } elseif ($record->jenis === 'Transfer Pemasukan') {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();

                            $relatedTransaction = Transaksi::where('transfer_code', $record->transfer_code)->first();

                            if ($relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo += $relatedTransaction->nominal;
                                } else {
                                    $relatedKas->saldo -= $relatedTransaction->nominal;
                                }

                                $relatedKas->save();
                                $relatedTransaction->delete();
                            }
                        } elseif (in_array($record->jenis, ['Pengeluaran', 'Pemasukan'])) {
                            if ($record->jenis == 'Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } else {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();
                        }
                    }),
            ])
            ->bulkActions([
                // DeleteBulkAction::make(),
            ]);
    }

    public static function getKategoriLabel(Transaksi $transaksi): ?string
    {
        return match ($transaksi->jenis) {
            'Transfer Pemasukan' => $transaksi->tipe_transfer === 'dompet'
                ? 'Transfer masuk dompet'
                : 'Transfer dari '.($transaksi->asal_buku_tabungan?->nama_buku ?? '-'),
            'Transfer Pengeluaran' => $transaksi->tipe_transfer === 'dompet'
                ? 'Transfer keluar dompet'
                : 'Transfer ke '.($transaksi->tujuan_buku_tabungan?->nama_buku ?? '-'),
            default => $transaksi->jenis_transaksi?->nama_jenis,
        };
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
