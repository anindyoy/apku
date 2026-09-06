<?php

namespace App\Filament\Resources\DompetResource\Pages;

use App\Filament\Resources\AuditSaldoDompetResource;
use App\Filament\Resources\DompetResource;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\AuditSaldoDompetService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDompet extends ListRecords
{
    protected static string $resource = DompetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auditSaldo')
                ->label('Audit saldo')
                ->icon('heroicon-o-clipboard-document-check')
                ->modalHeading('Cocokkan saldo aplikasi dengan saldo riil')
                ->modalDescription('Masukkan saldo riil setiap dompet. Selisih akan dicatat sebagai transaksi kategori Audit Saldo.')
                ->form([
                    Select::make('buku_kas_id')
                        ->label('Buku kas pencatatan')
                        ->options(fn (): array => Transaksi::opsiBukuKasYangDapatDikelola())
                        ->default(fn (): ?int => auth()->user()->idBukuKasUtama())
                        ->required(),
                    DateTimePicker::make('tanggal')
                        ->default(now())
                        ->seconds(false)
                        ->native(false)
                        ->maxDate(now())
                        ->required(),
                    Textarea::make('catatan')
                        ->label('Catatan audit')
                        ->placeholder('Contoh: Rekonsiliasi saldo September 2026')
                        ->required()
                        ->maxLength(1000),
                    Repeater::make('rincian')
                        ->label('Saldo per dompet')
                        ->default(fn (): array => Dompet::query()
                            ->get()
                            ->filter(fn (Dompet $dompet): bool => auth()->user()->dapatMengelolaTransaksiPadaDompet($dompet))
                            ->map(fn (Dompet $dompet): array => [
                                'dompet_id' => $dompet->id,
                                'nama_dompet' => $dompet->nama_dompet,
                                'saldo_aplikasi' => (int) $dompet->saldo,
                                'saldo_riil' => (int) $dompet->saldo,
                                'catatan' => null,
                            ])->values()->all())
                        ->schema([
                            Hidden::make('dompet_id'),
                            TextInput::make('nama_dompet')
                                ->label('Dompet')
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('saldo_aplikasi')
                                ->label('Saldo aplikasi')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('saldo_riil')
                                ->label('Saldo riil')
                                ->prefix('Rp')
                                ->numeric()
                                ->required(),
                            TextInput::make('catatan')
                                ->label('Catatan dompet')
                                ->maxLength(500),
                        ])
                        ->columns(2)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(AuditSaldoDompetService::class)->simpan(
                        auth()->user(),
                        BukuKas::findOrFail($data['buku_kas_id']),
                        $data['tanggal'],
                        $data['catatan'],
                        $data['rincian'],
                    );

                    Notification::make()
                        ->title('Audit saldo berhasil disimpan')
                        ->success()
                        ->send();
                }),
            Action::make('riwayatAudit')
                ->label('Riwayat audit')
                ->icon('heroicon-o-clock')
                ->url(AuditSaldoDompetResource::getUrl()),
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()->dapatMembuatDompet())
                ->before(fn () => abort_unless(auth()->user()->dapatMembuatDompet(), 403))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $data['is_default'] = ! auth()->user()->dompet()->exists();

                    return $data;
                }),
        ];
    }
}
