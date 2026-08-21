<?php

use Livewire\Livewire;

// ==================== PAGES ====================

test('halaman akun saya dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->assertSee($user->name);
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
        ->set('data.provinsi', 'Jawa Barat')
        ->set('data.kota', 'Bandung')
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
