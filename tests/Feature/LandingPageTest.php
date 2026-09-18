<?php

it('menampilkan landing page publik dengan navigasi utama dan ringkasan fitur', function () {
    $response = $this->get('/')->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('Catat uangmu.', 'Transaksi lengkap', 'Reguler', 'Premium aktif');
    expect($html)->toContain(asset('logo-options/apku-dompet-wordmark.svg'));
    expect($html)->toContain(route('filament.admin.auth.login'));
    expect($html)->toContain(route('filament.admin.auth.register'));
    expect($html)->toContain(route('tutorial'));
});
