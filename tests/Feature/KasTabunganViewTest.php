<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\User;
use Livewire\Livewire;

test('daftar kas menampilkan badge tipe kas biasa dan kas tabungan beserta progres', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    $kasBiasa = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Operasional',
        'saldo' => 1500000,
        'is_tabungan' => false,
    ]);

    $kasTabungan = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Liburan',
        'saldo' => 2000000,
        'is_tabungan' => true,
        'goal' => 5000000,
        'tanggal_goal' => today()->addDays(20),
    ]);

    $component = Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful()
        ->assertSeeText('Kas Biasa')
        ->assertSeeText('Kas Tabungan')
        ->assertSeeText('Target: Rp 5.000.000')
        ->assertSeeText('40%');

    expect($component->html())->toContain('style="width: 40%"');
});

test('dashboard menampilkan indikator tabungan dan progres pada section kas', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Rumah',
        'saldo' => 50000000,
        'is_tabungan' => true,
        'goal' => 100000000,
        'tanggal_goal' => today()->addDays(15),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Tabungan')
        ->assertSeeText('Target: Rp 100.000.000 (50%)');

    expect($component->html())->toContain('style="width: 50%"');
});
