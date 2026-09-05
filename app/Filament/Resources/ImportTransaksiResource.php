<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\ImportTransaksiResource\Pages\ListImportTransaksis;
use App\Models\ImportTransaksi;
use App\Services\ImportTransaksiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportTransaksiResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = ImportTransaksi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Riwayat Import';

    protected static ?string $modelLabel = 'Import transaksi';

    protected static ?string $pluralModelLabel = 'Riwayat import transaksi';

    protected static ?string $slug = 'riwayat-import-transaksi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu import')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('nama_file')
                    ->label('Nama file')
                    ->searchable(),
                TextColumn::make('jumlah_baris')
                    ->label('Jumlah transaksi')
                    ->numeric(),
                TextColumn::make('jumlah_diproses')
                    ->label('Progres')
                    ->formatStateUsing(fn (int $state, ImportTransaksi $record): string => number_format($state, 0, ',', '.').' / '.number_format($record->jumlah_baris, 0, ',', '.')),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu' => 'Menunggu',
                        'diproses' => 'Diproses',
                        'berhasil' => 'Berhasil',
                        'gagal' => 'Gagal',
                        default => 'Dibatalkan',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'diproses' => 'info',
                        'berhasil' => 'success',
                        'gagal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('pesan_error')
                    ->label('Keterangan')
                    ->limit(80)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('dibatalkan_at')
                    ->label('Waktu pembatalan')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('Batalkan')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan seluruh transaksi dari import ini?')
                    ->modalDescription('Seluruh transaksi dalam batch akan dihapus dan saldo buku kas serta dompet akan dipulihkan.')
                    ->visible(fn (ImportTransaksi $record): bool => $record->status === 'berhasil')
                    ->action(function (ImportTransaksi $record): void {
                        app(ImportTransaksiService::class)->batalkan(auth()->user(), $record);

                        Notification::make()
                            ->title('Batch import berhasil dibatalkan')
                            ->success()
                            ->send();
                    })
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportTransaksis::route('/'),
        ];
    }
}
