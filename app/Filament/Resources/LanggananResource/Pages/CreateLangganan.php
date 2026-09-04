<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\VoucherCode;
use App\Services\BuatOrderLangganan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
