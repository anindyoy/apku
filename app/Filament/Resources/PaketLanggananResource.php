<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaketLanggananResource\Pages\CreatePaketLangganan;
use App\Filament\Resources\PaketLanggananResource\Pages\EditPaketLangganan;
use App\Filament\Resources\PaketLanggananResource\Pages\ListPaketLangganans;
use App\Models\PaketLangganan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PaketLanggananResource extends Resource
{
    protected static ?string $model = PaketLangganan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Paket Langganan';

    protected static ?string $modelLabel = 'Paket langganan';

    protected static ?string $pluralModelLabel = 'Paket langganan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('label')->label('Label')->required()->maxLength(255),
            TextInput::make('harga')->label('Harga')->prefix('Rp')->numeric()->minValue(1)->required(),
            TextInput::make('durasi_hari')->label('Durasi')->suffix('hari')->numeric()->integer()->minValue(1)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('harga')->money('IDR')->sortable(),
                TextColumn::make('durasi_hari')->label('Durasi')->suffix(' hari')->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('langganans_count')->counts('langganans')->label('Total order'),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaketLangganans::route('/'),
            'create' => CreatePaketLangganan::route('/create'),
            'edit' => EditPaketLangganan::route('/{record}/edit'),
        ];
    }
}
