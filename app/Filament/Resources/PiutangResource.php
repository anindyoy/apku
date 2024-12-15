<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Pages\UtangPiutangDetail;
use App\Filament\Resources\PiutangResource\Pages;
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

            ->schema(UtangPiutang::formSchema())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->piutang()
                    ->selectRawNominalAndLastAcitivityDate()
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
            'index' => Pages\ListPiutangs::route('/'),
            // 'detail' => UtangPiutangDetail::route('/{record}/detail'),
            // 'create' => Pages\CreatePiutang::route('/create'),
            // 'edit' => Pages\EditPiutang::route('/{record}/edit'),
        ];
    }
}
