<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\UtangResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UtangResource\Pages\EditUtang;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\RelationManagers;
use App\Filament\Resources\UtangResource\Pages\CreateUtang;

class UtangResource extends Resource
{
    protected static ?string $model = UtangPiutang::class;
    protected static ?string $navigationGroup = 'Utang Piutang';
    protected static ?string $modelLabel = 'Utang';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            ->modifyQueryUsing(
                fn(Builder $query) => $query->utang()
            )
            ->columns(UtangPiutang::tableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUtangs::route('/'),
            'create' => Pages\CreateUtang::route('/create'),
            'edit' => Pages\EditUtang::route('/{record}/edit'),
        ];
    }
}
