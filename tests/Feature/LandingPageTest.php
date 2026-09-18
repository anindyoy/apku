<?php

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

it('menggunakan warna utama teal pada panel Filament seperti landing page', function () {
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());

    expect($panel->getColors()['primary'])->toBe(Color::Teal);
    expect(file_get_contents(resource_path('views/welcome.blade.php')))->toContain('bg-teal-700');
});
