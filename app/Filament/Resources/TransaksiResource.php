<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\TransaksiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Pages\CreateTransaksi;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $pluralLabel = 'Transaksi';
    protected static ?string $slug = 'transaksi';

    public static function form(Form $form): Form
    {
        return $form->schema(Transaksi::form());
    }

    public static function table(Table $table): Table
    {
        $transaksi = Transaksi::inRandomOrder()
            ->where('user_id', auth()->user()->id)
            ->first();

        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->select(
                    'transaksi.*',
                    DB::raw(
                        'SUM(CASE WHEN jenis = "Pemasukan" THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) as saldo'
                    )
                )
            )
            ->searchPlaceholder('Cari deskripsi...')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->visible(auth()->user()->isSuper()),

                IconColumn::make('jenis')
                    ->label('Tipe')
                    ->tooltip(fn($state) => $state)
                    ->icon(fn(string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        // 'draft' => 'gray',
                        // 'reviewing' => 'warning',
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('tanggal')
                    ->formatStateUsing(fn($state) => date('d M Y, H:i', strtotime($state))),

                Tables\Columns\TextColumn::make('jenis_transaksi.nama_jenis')
                    ->label('Kategori')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('deskripsi', 'like', "%{$search}%");
                    })
                    ->description(fn($record) => $record->deskripsi ? 'Deskripsi: ' . $record->deskripsi : '')
                    ->wrap(),
                // Tables\Columns\TextColumn::make('deskripsi')
                //     ->wrap()->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nominal')
                    ->numeric(),
                TextColumn::make('saldo'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(auth()->user()->isSuper()),
                DeleteAction::make()
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTransaksis::route('/'),
            // 'create' => Pages\CreateTransaksi::route('/create'),
            // 'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
