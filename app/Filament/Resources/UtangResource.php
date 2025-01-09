<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UtangResource\Pages;
use App\Filament\Resources\UtangResource\Pages\EditUtang;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\Pages\CreateUtang;
use App\Filament\Resources\UtangResource\Pages\UtangDetail;

class UtangResource extends Resource
{
    protected static ?string $model = UtangPiutang::class;
    protected static ?string $navigationGroup = 'Utang Piutang';
    protected static ?string $modelLabel = 'Utang';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(UtangPiutang::formSchema())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->utang()
                    ->selectRawNominalAndLastActivityDate()
            )
            ->searchPlaceholder('Cari nama..')
            ->columns(UtangPiutang::tableColumns())
            ->filters([
                //
            ])
            ->actions(UtangPiutang::tableActions());
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
            'detail' => UtangDetail::route('/{record}/detail'),
            // 'create' => Pages\CreateUtang::route('/create'),
            // 'edit' => Pages\EditUtang::route('/{record}/edit'),
        ];
    }
}
