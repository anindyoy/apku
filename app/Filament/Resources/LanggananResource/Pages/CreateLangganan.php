<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\VoucherCode;
use App\Services\BuatOrderLangganan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;

class CreateLangganan extends CreateRecord
{
    protected static string $resource = LanggananResource::class;

    protected static ?string $title = 'Buat Order Langganan';

    protected function handleRecordCreation(array $data): Model
    {
        $voucherCode = filled($data['kode_voucher'] ?? null)
            ? VoucherCode::query()->where('code', strtoupper(trim($data['kode_voucher'])))->first()
            : null;

        if (filled($data['kode_voucher'] ?? null) && $voucherCode === null) {
            throw ValidationException::withMessages([
                'data.kode_voucher' => 'Kode voucher tidak ditemukan.',
            ]);
        }

        return app(BuatOrderLangganan::class)->handle(
            auth()->user(),
            PaketLangganan::findOrFail($data['paket_langganan_id']),
            MetodePembayaran::findOrFail($data['metode_pembayaran_id']),
            $voucherCode,
        );
    }

    protected function afterCreate(): void
    {
        // Buka invoice di tab baru; redirect ke daftar tetap berjalan di tab lama.
        // Jika popup diblokir, notifikasi sukses menyediakan tombol Lihat invoice.
        $record = $this->getRecord();

        if ($record) {
            $this->js('window.open('.Js::from(route('langganan.invoice', $record)).", '_blank', 'noopener')");
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord();
        $url = $record ? route('langganan.invoice', $record) : static::getResource()::getUrl('index');

        return Notification::make()
            ->title('Order langganan berhasil dibuat')
            ->body('Invoice dibuka di tab baru. Gunakan Lihat invoice jika tab tidak terbuka otomatis.')
            ->success()
            ->actions([
                Action::make('lihatInvoice')
                    ->label('Lihat invoice')
                    ->url($url, shouldOpenInNewTab: true)
                    ->button(),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
