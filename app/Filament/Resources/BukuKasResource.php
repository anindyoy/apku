<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BukuKasResource\Pages;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Validation\Rule;
use UnitEnum;

class BukuKasResource extends Resource
{
    protected static ?string $model = BukuKas::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

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
                    ->searchable(),

                TextColumn::make('saldo')
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
                EditAction::make(),

                // DeleteAction::make()
                //     ->hidden(fn($record) => $record->transaksi->count()),

                Action::make('Delete2')
                    ->visible(fn ($record) => $record->transaksi->count())
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
                    ->modelLabel('Pindahkan transaksi'),

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
