<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\PiutangResource\Pages;
use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\PiutangResource\Pages\PiutangDetail;
use App\Models\UtangPiutang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PiutangResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = UtangPiutang::class;

    protected static string|UnitEnum|null $navigationGroup = 'Utang Piutang';

    protected static ?string $navigationLabel = 'Piutang';

    protected static ?string $modelLabel = 'Piutang';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 2;

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
                fn (Builder $query) => $query->piutang()
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
            'index' => ListPiutangs::route('/'),
            'detail' => PiutangDetail::route('/{record}/detail'),
            // 'create' => Pages\CreatePiutang::route('/create'),
            // 'edit' => Pages\EditPiutang::route('/{record}/edit'),
        ];
    }
}
