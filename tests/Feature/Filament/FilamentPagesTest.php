<?php

use Livewire\Livewire;
use Illuminate\Support\Facades\DB;

// ==================== PAGES ====================

test('halaman akun saya dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful();
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengupdate profil', function () {
    // Seed minimal wilayah data for Select options
    DB::table('wilayah')->insert([
        ['kode' => '32', 'nama' => 'JAWA BARAT'],
        ['kode' => '32.73', 'nama' => 'KOTA BANDUNG'],
    ]);

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
    // Seed minimal wilayah data for Select options
    DB::table('wilayah')->insert([
        ['kode' => '32', 'nama' => 'JAWA BARAT'],
        ['kode' => '32.73', 'nama' => 'KOTA BANDUNG'],
    ]);

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
