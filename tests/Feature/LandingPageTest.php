<?php

use App\Models\PaketLangganan;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;

it('menampilkan landing page publik dengan navigasi utama dan ringkasan fitur', function () {
    $response = $this->get('/')->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('Catat uangmu.', 'Transaksi lengkap', 'Reguler', 'Premium aktif');
    expect($html)->toContain(asset('logo-options/apku-dompet-wordmark.svg'));
    expect($html)->toContain(route('filament.admin.auth.login'));
    expect($html)->toContain(route('filament.admin.auth.register'));
    expect($html)->toContain(route('tutorial'));
});

it('menampilkan harga paket aktif di halaman depan dengan hemat per bulan', function () {
    // Bersihkan paket bawaan seeder agar skenario uji terkendali.
    \App\Models\Langganan::query()->delete();
    \App\Models\VoucherCode::query()->delete();
    \App\Models\Voucher::query()->delete();
    PaketLangganan::query()->delete();

    $satuBulan = PaketLangganan::factory()->create(['label' => 'Landing 1 Bulan', 'harga' => 20000, 'durasi_hari' => 30, 'is_active' => true]);
    $satuTahun = PaketLangganan::factory()->create(['label' => 'Landing 1 Tahun', 'harga' => 192000, 'durasi_hari' => 365, 'is_active' => true]);
    $arsip = PaketLangganan::factory()->create(['label' => 'Landing Arsip', 'harga' => 10000, 'durasi_hari' => 30, 'is_active' => false]);

    $html = $this->get('/')->assertOk()->getContent();

    // Paket aktif tampil dengan harga dan hitungan per bulan, paket nonaktif disembunyikan.
    expect($html)->toContain('Harga Premium', 'data-daftar-harga-paket', $satuBulan->label, $satuTahun->label);
    expect($html)->toContain('Rp20.000', 'Rp192.000');
    expect($html)->toContain('/ bulan', 'Paling hemat');
    expect($html)->not->toContain($arsip->label);

    // Urutan terpanjang dulu: 1 tahun muncul sebelum 1 bulan.
    expect(strpos($html, 'Landing 1 Tahun') < strpos($html, 'Landing 1 Bulan'))->toBeTrue();
});

it('menampilkan pesan saat belum ada paket aktif di halaman depan', function () {
    // Kosongkan paket agar cabang status kosong pada landing teruji.
    \App\Models\Langganan::query()->delete();
    \App\Models\VoucherCode::query()->delete();
    \App\Models\Voucher::query()->delete();
    PaketLangganan::query()->delete();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('Belum ada paket Premium yang tersedia.');
    expect($html)->not->toContain('data-daftar-harga-paket');
});

it('menggunakan warna utama teal pada panel Filament seperti landing page', function () {
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());

    expect($panel->getColors()['primary'])->toBe(Color::Teal);
    expect(file_get_contents(resource_path('views/welcome.blade.php')))->toContain('bg-teal-700');
});

it('menampilkan logo dompet wordmark sebagai brand panel admin', function () {
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());

    // Pastikan brand di atas menu memakai wordmark yang sama dengan landing page.
    expect($panel->getBrandName())->toBe('APKu');
    expect($panel->getBrandLogo())->toContain('logo-options/apku-dompet-wordmark.svg');
    expect($panel->getBrandLogoHeight())->toBe('2.5rem');
    expect($panel->getFavicon())->toContain('logo-options/apku-dompet.svg');
});
