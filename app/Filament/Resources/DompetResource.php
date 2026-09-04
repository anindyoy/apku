<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\TransferDompetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class DompetResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = Dompet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Dompet';

    protected static ?string $slug = 'dompet';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('nama_dompet')
                ->label('Nama dompet')
                ->required()
                ->maxLength(50)
                ->rules(fn (?Dompet $record): array => [
                    Rule::unique('dompet', 'nama_dompet')
                        ->where('user_id', auth()->id())
                        ->ignore($record?->id),
                ]),
            TextInput::make('saldo')
                ->prefix('Rp')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(),
            TextInput::make('description')
                ->label('Deskripsi')
                ->maxLength(200),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_dompet')->label('Dompet')->searchable(),
                TextColumn::make('saldo')
                    ->prefix('Rp ')
                    ->numeric()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'gray'),
                IconColumn::make('is_default')->label('Default')->boolean(),
                TextColumn::make('status_akses')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Dompet $record): string => auth()->user()->dapatMengelolaTransaksiPadaDompet($record) ? 'Aktif' : 'Terbatas')
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'danger'),
                TextColumn::make('description')->label('Deskripsi'),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (Dompet $record): bool => auth()->user()->dapatMengelolaTransaksiPadaDompet($record)),
                Action::make('jadikanDefault')
                    ->label('Jadikan default')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Dompet $record): bool => ! $record->is_default && auth()->user()->dapatMengelolaTransaksiPadaDompet($record))
                    ->action(function (Dompet $record): void {
                        Dompet::where('user_id', $record->user_id)->update(['is_default' => false]);
                        $record->update(['is_default' => true]);
                    }),
                Action::make('pindahkanDanHapus')
                    ->label('Hapus')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->visible(fn (Dompet $record): bool => ! $record->is_default
                        && auth()->user()->dapatMengelolaTransaksiPadaDompet($record)
                        && auth()->user()->dompet()->count() > 1)
                    ->form(fn (Dompet $record): array => [
                        Select::make('dompet_tujuan_id')
                            ->label('Dompet tujuan')
                            ->options(fn (): array => array_filter(
                                Transaksi::opsiDompetYangDapatDikelola(),
                                fn ($id): bool => (int) $id !== (int) $record->id,
                                ARRAY_FILTER_USE_KEY,
                            ))
                            ->required(),
                        Select::make('buku_kas_id')
                            ->label('Buku kas pencatatan')
                            ->options(fn (): array => Transaksi::opsiBukuKasYangDapatDikelola())
                            ->default(fn (): ?int => auth()->user()->idBukuKasUtama())
                            ->required(),
                    ])
                    ->action(function (Dompet $record, array $data): void {
                        app(TransferDompetService::class)->pindahkanSaldoDanHapus(
                            auth()->user(),
                            $record,
                            Dompet::findOrFail($data['dompet_tujuan_id']),
                            BukuKas::findOrFail($data['buku_kas_id']),
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDompet::route('/')];
    }
}
