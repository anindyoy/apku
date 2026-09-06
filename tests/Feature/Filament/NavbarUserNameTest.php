<?php

use App\Models\User;

test('navbar menampilkan nama pengguna yang sedang login', function () {
    $user = User::factory()->make([
        'name' => 'Nama Pengguna Aktif',
    ]);

    $this->actingAs($user);

    $html = view('filament.components.navbar-user-name')->render();

    expect($html)
        ->toContain('data-testid="navbar-user-name"')
        ->toContain('color: var(--color-gray-700)')
        ->toContain('.dark [data-testid="navbar-user-name"]')
        ->toContain('color: var(--color-gray-200)')
        ->toContain('Hai, Nama Pengguna Aktif');
})->group('filament', 'navbar');

test('nama pengguna tidak ditampilkan ketika belum login', function () {
    $html = view('filament.components.navbar-user-name')->render();

    expect($html)->not->toContain('data-testid="navbar-user-name"');
})->group('filament', 'navbar');
