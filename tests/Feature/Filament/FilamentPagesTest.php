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

test('masa aktif premium di halaman akun saya hanya menampilkan tanggal', function () {
    $expiry = now()->setDate(2030, 4, 5)->setTime(15, 30);
    $user = createRegularUserWithBukuKas();
    $user->update(['type' => 'premium', 'masa_aktif' => $expiry]);

    $component = Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->assertFormFieldIsDisabled('masa_aktif');

    $expiryState = $component->instance()->form->getComponent('masa_aktif')->getState();

    expect($expiryState)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
})
    ->group('filament', 'pages');

test('tipe akun di halaman akun saya ditampilkan dengan huruf awal kapital', function () {
    $user = createRegularUserWithBukuKas();
    $user->update(['type' => 'reguler']);

    $component = Livewire::actingAs($user)->test(\App\Filament\Pages\AkunSaya::class);

    expect($component->instance()->form->getComponent('type')->getState())->toBe('Reguler');

    $user->update(['type' => 'premium']);
    $component->instance()->form->fill($user->fresh()->toArray());

    expect($component->instance()->form->getComponent('type')->getState())->toBe('Premium')
        ->and($user->fresh()->type)->toBe('premium');
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengupdate profil tanpa mengubah email', function () {
    $user = createRegularUserWithBukuKas();
    $email = $user->email;

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->assertFormFieldIsDisabled('email')
        ->set('data.name', 'Updated Name')
        ->set('data.email', 'updated@test.com')
        ->set('data.hp', '081234567890')
        ->set('data.provinsi', '32')
        ->set('data.kota', '32.73')
        ->set('data.penggunaan', 'Pribadi/Keluarga')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Name', $user->fresh()->name);
    $this->assertEquals($email, $user->fresh()->email);
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

test('halaman kategori menggunakan label kategori', function () {
    $user = createRegularUserWithBukuKas();
    $halaman = Livewire::actingAs($user)->test(\App\Filament\Pages\Kategori::class);

    expect(\App\Filament\Pages\Kategori::getNavigationLabel())->toBe('Kategori')
        ->and($halaman->instance()->getTitle())->toBe('Kategori');
})
    ->group('filament', 'pages', 'label-kategori');

test('halaman kategori menampilkan panel kategori responsif dengan tabel terpisah', function () {
    $user = createRegularUserWithBukuKas();
    $halaman = Livewire::actingAs($user)->test(\App\Filament\Pages\Kategori::class);

    $halaman->assertSuccessful();
    $dom = new \DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$halaman->html());
    $xpath = new \DOMXPath($dom);

    foreach (['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'] as $jenis => $judul) {
        $panel = $xpath->query('//section[@aria-labelledby="kategori-'.$jenis.'-title"]');
        expect($panel->length)->toBe(1)
            ->and($xpath->query('.//h2', $panel->item(0))->item(0)->textContent)->toBe($judul)
            ->and($xpath->query('.//*[@*[name()="wire:id"]]', $panel->item(0))->length)->toBeGreaterThan(0);
    }

    expect($xpath->query('//section[contains(@class, "kategori-panel--pengeluaran")]')->length)->toBe(1);
})->group('filament', 'pages');
