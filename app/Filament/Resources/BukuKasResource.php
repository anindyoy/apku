<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BukuKasResource\Pages;
use App\Filament\Resources\BukuKasResource\RelationManagers;
use App\Models\BukuKas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BukuKasResource extends Resource
{
    protected static ?string $model = BukuKas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('nama_buku')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('saldo')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('goal')
                    ->numeric()
                    ->default(null),
                Forms\Components\DatePicker::make('tanggal_goal'),
                Forms\Components\TextInput::make('description')
                    ->maxLength(200)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_buku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('goal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_goal')
                    ->date()
                    ->sortable(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'create' => Pages\CreateBukuKas::route('/create'),
            'edit' => Pages\EditBukuKas::route('/{record}/edit'),
        ];
    }
}
