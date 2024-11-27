<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Piutang;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\PiutangResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PiutangResource\RelationManagers;
use App\Filament\Resources\PiutangResource\Pages\EditPiutang;
use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\PiutangResource\Pages\CreatePiutang;

class PiutangResource extends Resource
{
    protected static ?string $model = UtangPiutang::class;
    protected static ?string $navigationGroup = 'Utang Piutang';
    protected static ?string $modelLabel = 'Piutang';

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
                fn(Builder $query) => $query->piutang() 
                    ->selectRawNominalAndLastAcitivityDate()
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
            'index' => Pages\ListPiutangs::route('/'),
            'create' => Pages\CreatePiutang::route('/create'),
            'edit' => Pages\EditPiutang::route('/{record}/edit'),
        ];
    }
}
