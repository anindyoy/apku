<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\TransaksiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Pages\CreateTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\CustomTransaksi;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $pluralLabel = 'Transaksi';
    protected static ?string $slug = 'transaksi';
    protected static bool $shouldRegisterNavigation = false;

    // public static function form(Form $form): Form
    // {
    //     return $form->schema(Transaksi::form());
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->select(
                    'transaksi.*',
                    DB::raw(
                        'SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) as saldo'
                    )
                )
            )
            ->searchPlaceholder('Cari deskripsi...')
            ->paginated([10, 25, 50])
            ->columns([
                IconColumn::make('jenis')
                    ->label('Tipe')
                    ->tooltip(fn($state) => $state)
                    ->icon(fn(string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                        'Transfer Pemasukan' => 'heroicon-o-arrow-path-rounded-square',
                        'Transfer Pengeluaran' => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                        'Transfer Pemasukan' => 'primary',
                        'Transfer Pengeluaran' => 'primary',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->visible(auth()->user()->isSuper()),

                Tables\Columns\TextColumn::make('tanggal')
                    ->formatStateUsing(fn($state) => date('d M Y, H:i', strtotime($state))),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->getStateUsing(function ($record) {
                        if (!in_array($record->jenis, ['Transfer Pemasukan', 'Transfer Pengeluaran'])) {
                            $text = $record->jenis_transaksi?->nama_jenis;
                        }

                        if ($record->jenis == 'Transfer Pemasukan') {
                            $text = 'Transfer dari ' . $record->asal_buku_tabungan->nama_buku;
                        }

                        if ($record->jenis == 'Transfer Pengeluaran') {
                            $text = 'Transfer ke ' . $record->tujuan_buku_tabungan->nama_buku;
                        }

                        return $text;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('deskripsi', 'like', "%{$search}%");
                    })
                    ->description(
                        fn($record) => $record->deskripsi
                            ? ('Deskripsi: ' . $record->deskripsi) : ''
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nominal')
                    ->numeric()
                    ->prefix('Rp '),

                TextColumn::make('saldo')->numeric()
                    ->prefix('Rp '),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(auth()->user()->isSuper())
                    ->before(function ($record, $livewire) {
                        if (in_array($record->jenis, ['Transfer Pemasukan', 'Transfer Pengeluaran'])) {
                            $relatedTransactions = Transaksi::where('transfer_code', $record->transfer_code)->get();
                            $nominal_baru = $livewire->mountedTableActionsData[0]['nominal'];
                            $selisih = $nominal_baru - $record->nominal;

                            foreach ($relatedTransactions as $relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo -= $selisih;
                                } else if ($relatedTransaction->jenis === 'Transfer Pemasukan') {
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
                    ->hidden(auth()->user()->isSuper())
                    ->after(function ($record) {
                        $kas = $record->buku_kas;
                        if (in_array($record->jenis, ['Transfer Pengeluaran', 'Transfer Pemasukan'])) {
                            $kas = $record->buku_kas;

                            if ($record->jenis === 'Transfer Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } else if ($record->jenis === 'Transfer Pemasukan') {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();

                            $relatedTransaction = Transaksi::where('transfer_code', $record->transfer_code)->first();

                            if ($relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo += $relatedTransaction->nominal;
                                } else
                                    $relatedKas->saldo -= $relatedTransaction->nominal;

                                $relatedKas->save();
                                $relatedTransaction->delete();
                            }
                        } else if (in_array($record->jenis, ['Pengeluaran', 'Pemasukan'])) {
                            if ($record->jenis == 'Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } else {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();
                        }
                    })
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            KasOverview::class
        ];
    }

    public static function getPages(): array
    {
        return [
            // 'index' => CustomTransaksi::route('/'),
            'index' => Pages\ListTransaksis::route('/'),
            // 'create' => Pages\CreateTransaksi::route('/create'),
            // 'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
