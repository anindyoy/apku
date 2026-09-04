<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages\CreateVoucher;
use App\Filament\Resources\VoucherResource\Pages\EditVoucher;
use App\Filament\Resources\VoucherResource\Pages\ListVouchers;
use App\Models\Voucher;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Voucher';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('label')->required()->maxLength(255),
            DatePicker::make('masa_aktif')
                ->label('Tanggal kedaluwarsa')
                ->helperText('Kosongkan jika voucher tidak memiliki tanggal kedaluwarsa.'),
            TextInput::make('jumlah_diskon')
                ->label('Jumlah diskon')
                ->suffix('%')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(100)
                ->required(),
            Toggle::make('dapat_dipakai_berulang')
                ->label('Dapat dipakai berulang')
                ->helperText('Jika aktif, setiap kode dapat digunakan berkali-kali oleh siapa pun.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('jumlah_diskon')->label('Diskon')->suffix('%')->sortable(),
                TextColumn::make('masa_aktif')->label('Kedaluwarsa')->date('d M Y')->placeholder('Tanpa batas'),
                IconColumn::make('dapat_dipakai_berulang')->label('Berulang')->boolean(),
                TextColumn::make('codes_count')->counts('codes')->label('Jumlah kode'),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit' => EditVoucher::route('/{record}/edit'),
        ];
    }
}
