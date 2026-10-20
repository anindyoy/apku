<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\User;
use Livewire\Livewire;

test('pengguna premium dapat membuat kas tabungan dengan target nominal dan tenggat waktu', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    Dompet::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Dompet Utama',
    ]);

    $deadline = today()->addDays(60);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: [
            'nama_buku' => 'Tabungan Umroh',
            'saldo' => 500000,
            'is_tabungan' => true,
            'goal' => 25000000,
            'tanggal_goal' => $deadline->toDateString(),
            'hubungkan_kategori' => false,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('buku_kas', [
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Umroh',
        'is_tabungan' => true,
        'goal' => 25000000,
        'tanggal_goal' => $deadline->toDateString(),
    ]);
});

test('pengguna reguler non premium gagal membuat kas tabungan', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => null,
    ]);

    Dompet::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Dompet Utama',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: [
            'nama_buku' => 'Tabungan Gagal',
            'saldo' => 50000,
            'is_tabungan' => true,
            'goal' => 500000,
            'hubungkan_kategori' => false,
        ])
        ->assertHasActionErrors(['is_tabungan']);

    $this->assertDatabaseMissing('buku_kas', [
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Gagal',
    ]);
});

test('target nominal kas tabungan minimal 10000 jika diisi', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    Dompet::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Dompet Utama',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: [
            'nama_buku' => 'Tabungan Kecil',
            'saldo' => 5000,
            'is_tabungan' => true,
            'goal' => 5000,
            'hubungkan_kategori' => false,
        ])
        ->assertHasActionErrors(['goal']);
});

test('tenggat waktu kas tabungan tidak boleh di masa lalu saat dibuat', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    Dompet::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Dompet Utama',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: [
            'nama_buku' => 'Tabungan Kemarin',
            'saldo' => 50000,
            'is_tabungan' => true,
            'goal' => 1000000,
            'tanggal_goal' => today()->subDay()->toDateString(),
            'hubungkan_kategori' => false,
        ])
        ->assertHasActionErrors(['tanggal_goal']);
});

test('mengubah kas tabungan menjadi kas biasa mempertahankan goal dan deadline di database', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    $deadline = today()->addDays(90);

    $kas = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Lama',
        'is_tabungan' => true,
        'goal' => 10000000,
        'tanggal_goal' => $deadline->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callTableAction('edit', $kas, data: [
            'nama_buku' => 'Kas Biasa Baru',
            'is_tabungan' => 0,
        ])
        ->assertHasNoTableActionErrors();

    $kasSegar = $kas->fresh();
    expect($kasSegar->nama_buku)->toBe('Kas Biasa Baru')
        ->and($kasSegar->is_tabungan)->toBeFalse()
        ->and($kasSegar->goal)->toBe(10000000)
        ->and($kasSegar->tanggal_goal?->toDateString())->toBe($deadline->toDateString());
});

test('pengguna reguler non premium tidak dapat mengubah goal kas tabungan yang sudah ada', function () {
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => null,
    ]);

    $kas = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Saat Premium Dulu',
        'is_tabungan' => true,
        'goal' => 5000000,
        'tanggal_goal' => today()->addDays(10)->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callTableAction('edit', $kas, data: [
            'nama_buku' => 'Tabungan Saat Premium Dulu',
            'is_tabungan' => true,
            'goal' => 6000000,
            'tanggal_goal' => today()->addDays(10)->toDateString(),
        ])
        ->assertHasTableActionErrors(['goal']);
});

test('kolaborator kas tidak dapat mengedit kas tabungan milik pemilik', function () {
    $pemilik = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    $kolaborator = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
        'masa_aktif' => today()->addMonth(),
    ]);

    $kas = BukuKas::factory()->create([
        'user_id' => $pemilik->id,
        'nama_buku' => 'Tabungan Bersama',
        'is_tabungan' => true,
        'goal' => 5000000,
    ]);

    \App\Models\ShareBuku::create([
        'buku_kas_id' => $kas->id,
        'user_id' => $kolaborator->id,
        'privilege' => 'editor',
    ]);

    Livewire::actingAs($kolaborator)
        ->test(ListBukuKas::class)
        ->assertTableActionHidden('edit', $kas);
});
