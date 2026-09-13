<?php

use Livewire\Livewire;

// ==================== PAGES ====================

test('halaman akun saya dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful();
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengupdate profil', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->set('data.email', 'updated@test.com')
        ->set('data.hp', '081234567890')
        ->set('data.provinsi', '32')
        ->set('data.kota', '32.73')
        ->set('data.penggunaan', 'Pribadi/Keluarga')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Name', $user->fresh()->name);
    $this->assertEquals('updated@test.com', $user->fresh()->email);
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengubah password', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->set('data.provinsi', '32')
        ->set('data.kota', '32.73')
        ->set('data.penggunaan', 'Pribadi/Keluarga')
        ->set('data.password', 'newpassword123')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertTrue(password_verify('newpassword123', $user->fresh()->password));
})
    ->group('filament', 'pages');

test('halaman kategori dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\Kategori::class)
        ->assertSuccessful();
})
    ->group('filament', 'pages');

test('halaman kategori menggunakan label aktivitas', function () {
    $user = createRegularUserWithBukuKas();
    $halaman = Livewire::actingAs($user)->test(\App\Filament\Pages\Kategori::class);

    expect(\App\Filament\Pages\Kategori::getNavigationLabel())->toBe('Aktivitas')
        ->and($halaman->instance()->getTitle())->toBe('Aktivitas');
})
    ->group('filament', 'pages', 'label-aktivitas');

test('halaman kategori menampilkan panel aktivitas responsif dengan tabel terpisah', function () {
    $user = createRegularUserWithBukuKas();
    $halaman = Livewire::actingAs($user)->test(\App\Filament\Pages\Kategori::class);

    $halaman->assertSuccessful();
    $dom = new \DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$halaman->html());
    $xpath = new \DOMXPath($dom);

    foreach (['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'] as $jenis => $judul) {
        $panel = $xpath->query('//section[@aria-labelledby="aktivitas-'.$jenis.'-title"]');
        expect($panel->length)->toBe(1)
            ->and($xpath->query('.//h2', $panel->item(0))->item(0)->textContent)->toBe($judul)
            ->and($xpath->query('.//*[@*[name()="wire:id"]]', $panel->item(0))->length)->toBeGreaterThan(0);
    }

    expect($xpath->query('//section[contains(@class, "aktivitas-panel--pengeluaran")]')->length)->toBe(1);
})->group('filament', 'pages');
