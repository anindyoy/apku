<?php

namespace App\Filament\Concerns;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Services\AuditSaldoDompetService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

trait HasAuditSaldoAction
{
    private const PECAHAN_UANG = [
        'Uang Kertas' => [
            'kertas_100000' => 100000,
            'kertas_50000' => 50000,
            'kertas_20000' => 20000,
            'kertas_10000' => 10000,
            'kertas_5000' => 5000,
            'kertas_2000' => 2000,
            'kertas_1000' => 1000,
        ],
        'Uang Logam' => [
            'logam_1000' => 1000,
            'logam_500' => 500,
            'logam_200' => 200,
            'logam_100' => 100,
        ],
    ];

    protected function auditSaldoAction(): Action
    {
        return Action::make('auditSaldo')
            ->label('Audit saldo')
            ->icon('heroicon-o-clipboard-document-check')
            ->modalHeading('Cocokkan saldo aplikasi dengan saldo riil')
            ->modalDescription('Masukkan saldo riil setiap dompet. Selisih akan dicatat sebagai transaksi penyesuaian Audit Saldo.')
            ->form([
                Select::make('buku_kas_id')
                    ->label('Kas pencatatan')
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
                            ->required()
                            ->afterStateUpdated(function (mixed $state, Get $get, Set $set): mixed {
                                if (! $this->dompetMendukungHitungUang((int) ($get('dompet_id') ?? 0))) {
                                    return null;
                                }

                                return $set('jumlah_pecahan', $this->pecahanAwal());
                            }),
                        Section::make('Hitung uang kas')
                            ->description('Jumlah pecahan akan mengisi saldo riil secara otomatis. Saldo riil juga dapat diisi secara manual.')
                            ->visible(fn (Get $get): bool => $this->dompetMendukungHitungUang((int) ($get('dompet_id') ?? 0)))
                            ->schema([
                                ViewField::make('jumlah_pecahan')
                                    ->view('filament.forms.components.hitung-uang-kas')
                                    ->viewData(['kelompokPecahan' => self::PECAHAN_UANG])
                                    ->default(fn (): array => $this->pecahanAwal())
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function (?array $state, Get $get, Set $set): void {
                                        if (! $this->dompetMendukungHitungUang((int) ($get('dompet_id') ?? 0))) {
                                            return;
                                        }

                                        $total = $this->totalUangDihitung($state ?? []);
                                        $set('saldo_riil', $total ?? (int) $get('saldo_aplikasi'));
                                    }),
                            ])
                            ->columnSpanFull()
                            ->compact()
                            ->collapsible(),
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
                $data['rincian'] = array_map(function (array $item): array {
                    $dompetId = (int) ($item['dompet_id'] ?? 0);
                    $jumlahPecahan = $item['jumlah_pecahan'] ?? null;

                    if (! $this->dompetMendukungHitungUang($dompetId)) {
                        unset($item['jumlah_pecahan']);

                        return $item;
                    }

                    $total = $this->totalUangDihitung($jumlahPecahan ?? []);

                    if ($total !== null) {
                        $item['saldo_riil'] = $total;
                    }

                    unset($item['jumlah_pecahan']);

                    return $item;
                }, $data['rincian']);

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
            });
    }

    private function dompetMendukungHitungUang(int $dompetId): bool
    {
        if ($dompetId <= 0) {
            return false;
        }

        $dompet = Dompet::withoutGlobalScopes()->find($dompetId);

        return $dompet?->mendukungHitungUang() ?? false;
    }

    private function pecahanAwal(): array
    {
        return collect(self::PECAHAN_UANG)
            ->flatMap(fn (array $pecahan): array => array_fill_keys(array_keys($pecahan), null))
            ->all();
    }

    private function totalUangDihitung(mixed $pecahan): ?int
    {
        if (! is_array($pecahan)) {
            throw ValidationException::withMessages([
                'rincian' => 'Data hitung uang tidak valid.',
            ]);
        }

        $nominalDiisi = false;
        $total = 0;

        foreach (self::PECAHAN_UANG as $daftarNominal) {
            foreach ($daftarNominal as $kunci => $nominal) {
                $jumlah = $pecahan[$kunci] ?? null;

                if ($jumlah === null || $jumlah === '') {
                    continue;
                }

                $nominalDiisi = true;

                if ((! is_int($jumlah) && ! is_string($jumlah)) || ! preg_match('/^\d+$/D', (string) $jumlah)) {
                    throw ValidationException::withMessages([
                        'rincian' => 'Jumlah lembar atau keping harus berupa bilangan bulat nonnegatif.',
                    ]);
                }

                $jumlah = (int) $jumlah;

                if ($jumlah > 999999 || $jumlah > intdiv(PHP_INT_MAX - $total, $nominal)) {
                    throw ValidationException::withMessages([
                        'rincian' => 'Jumlah lembar atau keping melebihi batas yang dapat dihitung.',
                    ]);
                }

                $total += $nominal * $jumlah;
            }
        }

        return $nominalDiisi ? $total : null;
    }
}
