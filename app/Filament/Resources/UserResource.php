<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\Pages\EditUser;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\DateTimePicker::make('masa_aktif'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(fn($record) => 'Email: ' . $record->email)
                    ->searchable(['name', 'email']),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->description(fn($record) => $record->masa_aktif ? ('Masa aktif: ' . $record->masa_aktif) : null),

                TextColumn::make('buku_kas_count')
                    ->counts('buku_kas')
                    ->sortable()
                    ->label('Total Buku Kas'),

                TextColumn::make('transaksi_count')
                    ->counts('transaksi')
                    ->sortable()
                    ->label('Total Transaksi'),

                TextColumn::make('utang_piutang_count')
                    ->counts('utang_piutang')
                    ->sortable()
                    ->label('Total Utang Piutang'),

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
            ->actions(array_merge(
                [
                    EditAction::make(),
                ],
                class_exists(Impersonate::class)
                    ? [Impersonate::make()->visible(auth()->user()->isSuper())]
                    : [],
            ))
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
