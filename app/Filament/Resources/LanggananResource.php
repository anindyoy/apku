<?php

namespace App\Filament\Resources;

use App\Enums\StatusLangganan;
use App\Filament\Resources\LanggananResource\Pages\CreateLangganan;
use App\Filament\Resources\LanggananResource\Pages\ListLangganans;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Services\BatalkanLangganan;
use App\Services\KonfirmasiPembayaranLangganan;
use App\Services\OpsiSelectCache;
use App\Services\SetujuiLangganan;
use App\Services\TolakLangganan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class LanggananResource extends Resource
{
    protected static ?string $model = Langganan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?string $navigationLabel = 'Langganan Premium';

    protected static ?string $modelLabel = 'Langganan';

    protected static ?string $pluralModelLabel = 'Riwayat langganan';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('paket_langganan_id')
                ->label('Paket langganan')
                ->options(fn (): array => OpsiSelectCache::ingat('paket-langganan', fn (): array => PaketLangganan::query()
                    ->where('is_active', true)
                    ->orderBy('harga')
                    ->get()
                    ->mapWithKeys(fn (PaketLangganan $paket): array => [
                        $paket->id => $paket->label.' — Rp '.number_format($paket->harga, 0, ',', '.').' / '.$paket->durasi_hari.' hari',
                    ])->all()))
                ->searchable()
                ->required(),
            Select::make('metode_pembayaran_id')
                ->label('Metode pembayaran')
                ->options(fn (): array => OpsiSelectCache::ingat('metode-pembayaran', fn (): array => MetodePembayaran::query()
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->orderBy('label')
                    ->pluck('label', 'id')
                    ->all()))
                ->required(),
            TextInput::make('kode_voucher')
                ->label('Kode voucher')
                ->helperText('Opsional. Masukkan kode voucher jika tersedia.')
                ->maxLength(255),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'verifier']);

        return auth()->user()->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->contentGrid(['default' => 1, 'md' => 2])
            ->striped(false)
            ->columns([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Stack::make([
                        TextColumn::make('kode_order')->label('Kode order')->searchable()->copyable()
                            ->weight('bold')->icon('heroicon-o-document-text')
                            ->description(fn (Langganan $record): string => 'Tanggal order: '.$record->created_at->format('d M Y H:i'))
                            ->sortable(['created_at'])->wrap(),
                        TextColumn::make('user.name')->label('Pengguna')->icon('heroicon-o-user')->searchable()->wrap()->visible(fn (): bool => auth()->user()->isAdmin()),
                        TextColumn::make('label_paket')->label('Paket')->searchable()->wrap()
                            ->icon('heroicon-o-sparkles')->weight('medium')
                            ->description(fn (Langganan $record): string => 'Harga: Rp '.number_format($record->harga, 0, ',', '.')),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('total_pembayaran')->label('Total')->money('IDR', decimalPlaces: 0)->sortable()->wrap()
                            ->icon('heroicon-o-banknotes')->weight('bold')->size('lg')
                            ->description(fn (Langganan $record): string => collect([
                                filled($record->kode_voucher) ? 'Voucher: '.$record->kode_voucher : null,
                                'Diskon: Rp '.number_format($record->nominal_diskon, 0, ',', '.'),
                            ])->filter()->implode(' • ')),
                        TextColumn::make('label_metode_pembayaran')
                            ->label('Pembayaran')->icon('heroicon-o-credit-card')->wrap()
                            ->description(fn (Langganan $record): string => collect([
                                data_get($record->detail_pembayaran, 'nama_penyedia'),
                                data_get($record->detail_pembayaran, 'nomor_tujuan'),
                                data_get($record->detail_pembayaran, 'nama_pemilik'),
                                data_get($record->detail_pembayaran, 'instruksi'),
                            ])->filter()->implode(' • ')),
                        TextColumn::make('status')
                            ->badge()
                            ->formatStateUsing(fn (StatusLangganan $state): string => $state->label())
                            ->color(fn (StatusLangganan $state): string => $state->warna())
                            ->wrap()
                            ->description(fn (Langganan $record): string => collect([
                                'Aktif sampai: '.($record->masa_aktif_sampai?->format('d M Y') ?? '-'),
                                filled($record->catatan_admin) ? 'Catatan admin: '.$record->catatan_admin : null,
                            ])->filter()->implode(' • ')),
                    ])->space(2),
                ])->extraAttributes(['data-subscription-card' => 'true']),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(StatusLangganan::cases())
                    ->mapWithKeys(fn (StatusLangganan $status): array => [$status->value => $status->label()])
                    ->all()),
            ])
            ->actions([
                Action::make('lihatQr')
                    ->label('Lihat QR')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Langganan $record): string => Storage::disk('local')->temporaryUrl(
                        data_get($record->detail_pembayaran, 'gambar_qr_path'),
                        now()->addMinutes(5),
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn (Langganan $record): bool => filled(data_get($record->detail_pembayaran, 'gambar_qr_path'))),
                Action::make('lihatBukti')
                    ->label('Lihat bukti')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->url(fn (Langganan $record): string => Storage::disk('local')->temporaryUrl($record->bukti_pembayaran_path, now()->addMinutes(5)))
                    ->openUrlInNewTab()
                    ->visible(fn (Langganan $record): bool => filled($record->bukti_pembayaran_path)),
                Action::make('konfirmasiPembayaran')
                    ->label(fn (Langganan $record): string => $record->status === StatusLangganan::Ditolak ? 'Kirim ulang bukti' : 'Konfirmasi pembayaran')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('bukti_pembayaran_path')
                            ->label('Bukti pembayaran')
                            ->disk('local')
                            ->directory('langganan/bukti-pembayaran')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(3072)
                            ->required(),
                        Textarea::make('catatan_user')->label('Catatan')->maxLength(1000),
                    ])
                    ->action(function (Langganan $record, array $data): void {
                        app(KonfirmasiPembayaranLangganan::class)->handle(
                            auth()->user(),
                            $record,
                            $data['bukti_pembayaran_path'],
                            $data['catatan_user'] ?? null,
                        );
                        Notification::make()->title('Konfirmasi pembayaran berhasil dikirim')->success()->send();
                    })
                    ->visible(fn (Langganan $record): bool => ! auth()->user()->isAdmin()
                        && in_array($record->status, [StatusLangganan::MenungguPembayaran, StatusLangganan::Ditolak], true)),
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Langganan $record) => app(BatalkanLangganan::class)->handle(auth()->user(), $record))
                    ->visible(fn (Langganan $record): bool => ! auth()->user()->isAdmin()
                        && $record->status === StatusLangganan::MenungguPembayaran),
                Action::make('setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([Textarea::make('catatan_admin')->label('Catatan')->maxLength(1000)])
                    ->action(function (Langganan $record, array $data): void {
                        app(SetujuiLangganan::class)->handle(auth()->user(), $record, $data['catatan_admin'] ?? null);
                        Notification::make()->title('Langganan berhasil disetujui')->success()->send();
                    })
                    ->visible(fn (Langganan $record): bool => auth()->user()->isAdmin()
                        && $record->status === StatusLangganan::MenungguVerifikasi),
                Action::make('tolak')
                    ->color('danger')
                    ->form([Textarea::make('catatan_admin')->label('Alasan penolakan')->required()->maxLength(1000)])
                    ->action(function (Langganan $record, array $data): void {
                        app(TolakLangganan::class)->handle(auth()->user(), $record, $data['catatan_admin']);
                        Notification::make()->title('Konfirmasi pembayaran ditolak')->warning()->send();
                    })
                    ->visible(fn (Langganan $record): bool => auth()->user()->isAdmin()
                        && $record->status === StatusLangganan::MenungguVerifikasi),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLangganans::route('/'),
            'create' => CreateLangganan::route('/create'),
        ];
    }
}
