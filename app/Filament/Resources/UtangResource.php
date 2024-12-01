<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\UtangResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UtangResource\Pages\EditUtang;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\RelationManagers;
use App\Filament\Resources\UtangResource\Pages\CreateUtang;
use App\Filament\Resources\UtangResource\Pages\UtangPiutangDetail;
use App\Filament\Resources\UtangResource\Widgets\UtangPiutangOverview;

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
            'index' => Pages\ListUtangs::route('/'),
            'detail' => UtangPiutangDetail::route('/{record}/detail'),
            // 'create' => Pages\CreateUtang::route('/create'),
            // 'edit' => Pages\EditUtang::route('/{record}/edit'),
        ];
    }
}
