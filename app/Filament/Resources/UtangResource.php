<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\UtangResource\Pages;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\Pages\UtangDetail;
use App\Models\UtangPiutang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UtangResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = UtangPiutang::class;

    protected static string|UnitEnum|null $navigationGroup = 'Utang Piutang';

    protected static ?string $navigationLabel = 'Utang';

    protected static ?string $modelLabel = 'Utang';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(UtangPiutang::formSchema())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query->utang()
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
            'index' => ListUtangs::route('/'),
            'detail' => UtangDetail::route('/{record}/detail'),
            // 'create' => Pages\CreateUtang::route('/create'),
            // 'edit' => Pages\EditUtang::route('/{record}/edit'),
        ];
    }
}
