<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UtangPiutangResource\Pages;
use App\Filament\Resources\UtangPiutangResource\RelationManagers;
use App\Models\UtangPiutang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UtangPiutangResource extends Resource
{
    protected static ?string $model = UtangPiutang::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $pluralLabel = 'Utang Piutang';
    protected static ?string $slug = 'utang-piutang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListUtangPiutangs::route('/'),
            'create' => Pages\CreateUtangPiutang::route('/create'),
            'edit' => Pages\EditUtangPiutang::route('/{record}/edit'),
        ];
    }
}
