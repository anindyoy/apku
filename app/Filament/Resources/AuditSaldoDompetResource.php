<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Filament\Resources\AuditSaldoDompetResource\Pages\ListAuditSaldoDompet;
use App\Models\AuditSaldoDompet;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AuditSaldoDompetResource extends Resource
{
    use HidesFromAdminNavigation;

    protected static ?string $model = AuditSaldoDompet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Audit Saldo';

    protected static ?string $slug = 'audit-saldo-dompet';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('bukuKas.nama_buku')->label('Buku kas'),
                TextColumn::make('total_saldo_aplikasi')->label('Saldo aplikasi')->money('IDR'),
                TextColumn::make('total_saldo_riil')->label('Saldo riil')->money('IDR'),
                TextColumn::make('total_selisih')
                    ->label('Selisih')
                    ->money('IDR')
                    ->color(fn (int $state): string => $state === 0 ? 'success' : 'warning'),
                TextColumn::make('catatan')->limit(50)->wrap(),
            ])
            ->actions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail audit saldo')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (AuditSaldoDompet $record) => view('filament.audit-saldo-dompet-detail', [
                        'audit' => $record->load('detail.transaksi'),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditSaldoDompet::route('/')];
    }
}
