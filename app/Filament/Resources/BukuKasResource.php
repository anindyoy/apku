<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\BukuKas;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\BukuKasResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BukuKasResource\RelationManagers;
use App\Filament\Resources\BukuKasResource\Pages\EditBukuKas;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas;

class BukuKasResource extends Resource
{
    protected static ?string $model = BukuKas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('user_id')
                //     ->required()
                //     ->numeric(),

                Forms\Components\TextInput::make('nama_buku')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('saldo')
                    ->prefix('Rp')
                    ->required()
                    ->numeric(),

                // Forms\Components\TextInput::make('goal')
                //     ->numeric()
                //     ->default(null),
                // Forms\Components\DatePicker::make('tanggal_goal'),

                Forms\Components\TextInput::make('description')
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

                Tables\Columns\TextColumn::make('nama_buku')
                    ->searchable(),

                Tables\Columns\TextColumn::make('saldo')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('transaksi_count')
                    ->counts('transaksi')
                    ->label('Total Transaksi'),

                // Tables\Columns\TextColumn::make('goal')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('tanggal_goal')
                //     ->date()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // DeleteAction::make()
                //     ->hidden(fn($record) => $record->transaksi->count()),

                Action::make('Delete2')
                    ->visible(fn($record) => $record->transaksi->count())
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->label('Hapus')
                    ->form(function ($record) {
                        return [
                            Select::make('buku_kas_id')
                                ->options(
                                    BukuKas::whereNot('id', $record->id)
                                        ->pluck('nama_buku', 'id')
                                )
                                ->required(),
                        ];
                    })
                    ->modelLabel('Pindahkan transaksi')


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
            'index' => Pages\ListBukuKas::route('/'),
            // 'create' => Pages\CreateBukuKas::route('/create'),
            // 'edit' => Pages\EditBukuKas::route('/{record}/edit'),
        ];
    }
}
