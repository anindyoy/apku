<?php

use App\Filament\Resources\LanggananResource;
use App\Models\ApplicationSetting;
use App\Models\Langganan;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;

// Menjaga skenario tombol trial landing tetap terkendali dari paket bawaan seeder.
function siapkanLandingTrial(): void
{
    Langganan::query()->delete();
    VoucherCode::query()->delete();
    Voucher::query()->delete();
    PaketLangganan::query()->delete();
    ApplicationSetting::where('key', 'trial')->delete();
}

test('landing menampilkan tombol coba premium mengikuti durasi setting', function () {
    siapkanLandingTrial();
    ApplicationSetting::updateOrCreate(['key' => 'trial'], ['value' => ['aktif' => true, 'durasi_hari' => 14]]);

    // Tamu diarahkan ke registrasi, login diarahkan ke halaman Langganan.
    $htmlTamu = $this->get('/')->assertOk()->getContent();
    expect($htmlTamu)->toContain('Coba Premium gratis 14 hari', 'data-coba-premium', route('filament.admin.auth.register'));

    $pengguna = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
    $htmlMasuk = $this->actingAs($pengguna)->get('/')->assertOk()->getContent();
    expect($htmlMasuk)->toContain('Coba Premium gratis 14 hari', 'data-coba-premium', LanggananResource::getUrl('index'));
})->group('trial', 'landing');

test('landing menyembunyikan tombol coba premium saat program nonaktif', function () {
    siapkanLandingTrial();
    ApplicationSetting::updateOrCreate(['key' => 'trial'], ['value' => ['aktif' => false, 'durasi_hari' => 14]]);

    // Tanpa program aktif, teks dinamis dan tombol ikut disembunyikan.
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->not->toContain('data-coba-premium');
})->group('trial', 'landing');
