<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Forms\SumberDanaFormSchema;
use App\Filament\Resources\SumberDanaResource\Pages\ListSumberDana;
use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Services\TransferDompetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SumberDanaResource extends Resource
{
    use HidesFromAdminNavigation;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = Pengaturan::class;

    protected static ?string $model = SumberDana::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Sumber Dana';

    protected static ?string $slug = 'sumber-dana';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(SumberDanaFormSchema::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    TextColumn::make('nama_dompet')->label('Sumber dana')->searchable()->weight('bold')->size('lg')->wrap(),
                    TextColumn::make('saldo')
                        ->prefix('Saldo: Rp ')
                        ->numeric()
                        ->color(fn (int $state): string => $state < 0 ? 'danger' : 'gray'),
                    TextColumn::make('is_default')
                        ->label('Default')
                        ->prefix('Default: ')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                        ->badge()
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    TextColumn::make('status_akses')
                        ->label('Status')
                        ->prefix('Status: ')
                        ->badge()
                        ->getStateUsing(fn (SumberDana $record): string => auth()->user()->dapatMengelolaTransaksiPadaDompet($record) ? 'Aktif' : 'Terbatas')
                        ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'danger'),
                    TextColumn::make('description')->label('Deskripsi')->prefix('Deskripsi: ')->wrap(),
                ])->space(3),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (SumberDana $record): bool => auth()->user()->dapatMengelolaTransaksiPadaDompet($record)),
                Action::make('jadikanDefault')
                    ->label('Jadikan default')
                    ->icon('heroicon-o-star')
                    ->visible(fn (SumberDana $record): bool => ! $record->is_default && auth()->user()->dapatMengelolaTransaksiPadaDompet($record))
                    ->action(function (SumberDana $record): void {
                        SumberDana::where('user_id', $record->user_id)->update(['is_default' => false]);
                        $record->update(['is_default' => true]);
                    }),
                Action::make('pindahkanDanHapus')
                    ->label('Hapus')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->visible(fn (SumberDana $record): bool => ! $record->is_default
                        && auth()->user()->dapatMengelolaTransaksiPadaDompet($record)
                        && auth()->user()->sumberDana()->count() > 1)
                    ->form(fn (SumberDana $record): array => [
                        Select::make('sumber_dana_tujuan_id')
                            ->label('Sumber dana tujuan')
                            ->options(fn (): array => array_filter(
                                Transaksi::opsiSumberDanaYangDapatDikelola(),
                                fn ($id): bool => (int) $id !== (int) $record->id,
                                ARRAY_FILTER_USE_KEY,
                            ))
                            ->required(),
                        Select::make('buku_kas_id')
                            ->label('Kas pencatatan')
                            ->options(fn (): array => Transaksi::opsiBukuKasYangDapatDikelola())
                            ->default(fn (): ?int => auth()->user()->idBukuKasUtama())
                            ->required(),
                    ])
                    ->action(function (SumberDana $record, array $data): void {
                        app(TransferDompetService::class)->pindahkanSaldoDanHapus(
                            auth()->user(),
                            $record,
                            SumberDana::findOrFail($data['sumber_dana_tujuan_id']),
                            BukuKas::findOrFail($data['buku_kas_id']),
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSumberDana::route('/')];
    }
}
