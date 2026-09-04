<?php

use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\PiutangResource\Pages\PiutangDetail;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

function createPiutangTestData(User $user, string $kepada, int $nominal = 100000): UtangPiutang
{
    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => $kepada,
        'tipe' => 'piutang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $piutang->id,
        'nominal' => $nominal,
        'tipe' => 'tambah',
        'created_at' => now(), // Pastikan muncul di halaman pertama (sorted by last_activity_date)
    ]);

    return $piutang;
}

test('user bisa membuka halaman daftar piutang', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    createPiutangTestData($user, 'Budi Santoso');

    Livewire::actingAs($user)
        ->test(ListPiutangs::class)
        ->assertSuccessful();
})->group('piutang');

test('halaman daftar piutang hanya menampilkan data piutang milik user', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $otherUser = User::notAdmin()->whereNot('id', $user->id)->inRandomOrder()->first();

    createPiutangTestData($user, 'Piutang Milik Saya');
    createPiutangTestData($otherUser, 'Piutang Milik Orang Lain');

    Livewire::actingAs($user)
        ->test(ListPiutangs::class)
        ->assertSuccessful()
        ->assertSeeText('Piutang Milik Saya')
        ->assertDontSee('Piutang Milik Orang Lain');
})->group('piutang');

test('halaman daftar piutang hanya menampilkan data dengan tipe piutang', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    createPiutangTestData($user, 'Data Piutang Unik');
    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Data Utang Bukan Piutang',
        'tipe' => 'utang',
    ]);

    Livewire::actingAs($user)
        ->test(ListPiutangs::class)
        ->assertSuccessful()
        ->assertSeeText('Data Piutang Unik')
        ->assertDontSee('Data Utang Bukan Piutang');
})->group('piutang');

test('admin bisa melihat data piutang milik user lain', function () {
    $admin = User::admin()->first();
    $user = User::notAdmin()->inRandomOrder()->first();

    createPiutangTestData($user, 'Data Piutang Dari User Lain');

    Livewire::actingAs($admin)
        ->test(ListPiutangs::class)
        ->assertSuccessful()
        ->assertSeeText('Data Piutang Dari User Lain');
})->group('piutang');

test('user bisa membuka halaman detail piutang', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $piutang = createPiutangTestData($user, 'Budi Santoso', 250000);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSeeText('Piutang kepada Budi Santoso');
})->group('piutang');

test('user bisa menambah nominal piutang pada halaman detail', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $piutang = createPiutangTestData($user, 'Budi Santoso', 100000);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->callTableAction('tambah', data: [
            'nominal' => 50000,
            'deskripsi' => 'Tambah nominal piutang',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $piutang->id,
        'nominal' => 50000,
        'tipe' => 'tambah',
        'deskripsi' => 'Tambah nominal piutang',
    ]);
})->group('piutang');

test('user bisa mencatat pembayaran piutang pada halaman detail', function () {
    $user = User::notAdmin()->inRandomOrder()->first();
    $piutang = createPiutangTestData($user, 'Budi Santoso', 100000);

    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->callTableAction('kurang', data: [
            'nominal' => 40000,
            'deskripsi' => 'Bayar sebagian',
            'created_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('utang_piutang_detail', [
        'utang_piutang_id' => $piutang->id,
        'nominal' => 40000,
        'tipe' => 'kurang',
        'deskripsi' => 'Bayar sebagian',
    ]);
})->group('piutang');
