<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\Pages\UtangDetail;

function createUtangTestData(User $user, string $kepada, int $nominal = 100000): UtangPiutang
{
    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => $kepada,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => $nominal,
        'tipe' => 'tambah',
        'created_at' => now(), // Pastikan muncul di halaman pertama (sorted by last_activity_date)
    ]);

    return $utang;
}

test('user bisa membuka halaman daftar utang', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    createUtangTestData($user, 'Siti Aminah');

    Livewire::actingAs($user)
        ->test(ListUtangs::class)
        ->assertSuccessful();
})->group('utang');

test('halaman daftar utang hanya menampilkan data utang milik user', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $otherUser = User::notSuper()->whereNot('id', $user->id)->inRandomOrder()->first();

    createUtangTestData($user, 'Utang Milik Saya');
    createUtangTestData($otherUser, 'Utang Milik Orang Lain');

    Livewire::actingAs($user)
        ->test(ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Utang Milik Saya')
        ->assertDontSee('Utang Milik Orang Lain');
})->group('utang');

test('halaman daftar utang hanya menampilkan data dengan tipe utang', function () {
    $user = User::notSuper()->inRandomOrder()->first();

    createUtangTestData($user, 'Data Utang Unik');
    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Data Piutang Bukan Utang',
        'tipe' => 'piutang',
    ]);

    Livewire::actingAs($user)
        ->test(ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Data Utang Unik')
        ->assertDontSee('Data Piutang Bukan Utang');
})->group('utang');

test('super user bisa melihat data utang milik user lain', function () {
    $super = User::super()->first();
    $user = User::notSuper()->inRandomOrder()->first();

    createUtangTestData($user, 'Data Utang Dari User Lain');

    Livewire::actingAs($super)
        ->test(ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Data Utang Dari User Lain');
})->group('utang');

test('user bisa membuka halaman detail utang', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $utang = createUtangTestData($user, 'Siti Aminah', 250000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Utang kepada Siti Aminah');
})->group('utang');

test('user bisa menambah nominal utang pada halaman detail', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $utang = createUtangTestData($user, 'Siti Aminah', 100000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->callTableAction('tambah', data: [
            'nominal' => 50000,
            'deskripsi' => 'Tambah nominal utang',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $utang->id,
        'nominal' => 50000,
        'tipe' => 'tambah',
        'deskripsi' => 'Tambah nominal utang',
    ]);
})->group('utang');

test('user bisa mencatat pembayaran utang pada halaman detail', function () {
    $user = User::notSuper()->inRandomOrder()->first();
    $utang = createUtangTestData($user, 'Siti Aminah', 100000);

    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->callTableAction('kurang', data: [
            'nominal' => 40000,
            'deskripsi' => 'Bayar sebagian',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $utang->id,
        'nominal' => 40000,
        'tipe' => 'kurang',
        'deskripsi' => 'Bayar sebagian',
    ]);
})->group('utang');