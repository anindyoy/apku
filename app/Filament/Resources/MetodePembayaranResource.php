<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetodePembayaranResource\Pages\CreateMetodePembayaran;
use App\Filament\Resources\MetodePembayaranResource\Pages\EditMetodePembayaran;
use App\Filament\Resources\MetodePembayaranResource\Pages\ListMetodePembayarans;
use App\Models\MetodePembayaran;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MetodePembayaranResource extends Resource
{
    protected static ?string $model = MetodePembayaran::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Metode Pembayaran';

    protected static ?string $modelLabel = 'Metode pembayaran';

    protected static ?string $pluralModelLabel = 'Metode pembayaran';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('label')->required()->maxLength(255),
            Select::make('jenis')->options([
                'bank' => 'Transfer bank',
                'dompet_digital' => 'Dompet digital',
                'qr' => 'QR',
                'lainnya' => 'Lainnya',
            ])->required(),
            TextInput::make('nama_penyedia')->label('Nama penyedia')->required()->maxLength(255),
            TextInput::make('nomor_tujuan')->label('Nomor rekening/akun')->maxLength(255),
            TextInput::make('nama_pemilik')->label('Nama pemilik')->maxLength(255),
            Textarea::make('instruksi')->rows(4)->columnSpanFull(),
            FileUpload::make('gambar_qr_path')
                ->label('Gambar QR')
                ->disk('local')
                ->directory('metode-pembayaran/qr')
                ->visibility('private')
                ->image()
                ->maxSize(3072),
            TextInput::make('urutan')->numeric()->integer()->minValue(0)->default(0)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('jenis')->badge(),
                TextColumn::make('nama_penyedia')->label('Penyedia')->searchable(),
                TextColumn::make('nomor_tujuan')->label('Nomor tujuan'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('urutan')->sortable(),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMetodePembayarans::route('/'),
            'create' => CreateMetodePembayaran::route('/create'),
            'edit' => EditMetodePembayaran::route('/{record}/edit'),
        ];
    }
}
