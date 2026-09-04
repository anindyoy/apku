<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherCodeResource\Pages\CreateVoucherCode;
use App\Filament\Resources\VoucherCodeResource\Pages\EditVoucherCode;
use App\Filament\Resources\VoucherCodeResource\Pages\ListVoucherCodes;
use App\Models\Voucher;
use App\Models\VoucherCode;
use App\Services\OpsiSelectCache;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class VoucherCodeResource extends Resource
{
    protected static ?string $model = VoucherCode::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Kode Voucher';

    protected static ?string $modelLabel = 'Kode voucher';

    protected static ?string $pluralModelLabel = 'Kode voucher';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('voucher_id')
                ->label('Voucher')
                ->options(fn (): array => OpsiSelectCache::ingat('voucher', fn (): array => Voucher::query()
                    ->orderBy('label')
                    ->pluck('label', 'id')
                    ->all()))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state): string => Str::upper(trim($state))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->copyable(),
                TextColumn::make('voucher.label')->label('Voucher')->searchable()->sortable(),
                TextColumn::make('voucher.jumlah_diskon')->label('Diskon')->suffix('%'),
                TextColumn::make('voucher.masa_aktif')->label('Kedaluwarsa')->date('d M Y')->placeholder('Tanpa batas'),
                IconColumn::make('voucher.dapat_dipakai_berulang')->label('Berulang')->boolean(),
                TextColumn::make('langganans_count')->counts('langganans')->label('Jumlah pemakaian'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoucherCodes::route('/'),
            'create' => CreateVoucherCode::route('/create'),
            'edit' => EditVoucherCode::route('/{record}/edit'),
        ];
    }
}
