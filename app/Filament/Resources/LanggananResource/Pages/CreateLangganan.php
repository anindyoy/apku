<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Services\BuatOrderLangganan;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;

class CreateLangganan extends CreateRecord
{
    protected static string $resource = LanggananResource::class;

    protected static ?string $title = 'Buat Order Langganan';

    protected function handleRecordCreation(array $data): Model
    {
        return app(BuatOrderLangganan::class)->handle(
            auth()->user(),
            PaketLangganan::findOrFail($data['paket_langganan_id']),
            MetodePembayaran::findOrFail($data['metode_pembayaran_id']),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
