<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\User;
use App\Services\DashboardCache;
use Livewire\Livewire;

test('default hitung_tabungan_di_total adalah true pada user baru', function () {
    $user = User::factory()->create();

    expect($user->hitungTabunganDiTotal())->toBeTrue()
        ->and($user->fresh()->hitung_tabungan_di_total)->toBeTrue();
});

test('pengaturan tampilan di list kas dapat mengubah hitung_tabungan_di_total', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
        'hitung_tabungan_di_total' => true,
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('pengaturanTampilan', data: [
            'hitung_tabungan_di_total' => false,
        ])
        ->assertHasNoActionErrors();

    expect($user->fresh()->hitung_tabungan_di_total)->toBeFalse()
        ->and($user->fresh()->hitungTabunganDiTotal())->toBeFalse();
});

test('dashboard menghitung total saldo kas tabungan saat hitung_tabungan_di_total aktif', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
        'hitung_tabungan_di_total' => true,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Biasa',
        'saldo' => 1000000,
        'is_tabungan' => false,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tabungan',
        'saldo' => 500000,
        'is_tabungan' => true,
        'goal' => 2000000,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Total saldo kas')
        ->assertSeeText('Rp 1.500.000')
        ->assertSeeText('Tersebar di 2 kas');
});

test('dashboard memisahkan saldo kas tabungan dari total saldo saat hitung_tabungan_di_total nonaktif', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
        'hitung_tabungan_di_total' => false,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Biasa',
        'saldo' => 1000000,
        'is_tabungan' => false,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tabungan',
        'saldo' => 500000,
        'is_tabungan' => true,
        'goal' => 2000000,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Total saldo kas biasa')
        ->assertSeeText('Rp 1.000.000')
        ->assertSeeText('Tersebar di 2 kas (saldo tabungan dipisahkan)')
        ->assertSeeText('Kas Tabungan');
});

test('widget kas overview menyesuaikan label saat hitung_tabungan_di_total nonaktif', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
        'hitung_tabungan_di_total' => false,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Biasa',
        'saldo' => 1000000,
        'is_tabungan' => false,
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tabungan',
        'saldo' => 500000,
        'is_tabungan' => true,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful();

    $widget = Livewire::actingAs($user)->test(KasOverview::class);
    $widget->assertSuccessful();
    expect($widget->html())->toContain('Kas Biasa Rp 1,000,000');
});
