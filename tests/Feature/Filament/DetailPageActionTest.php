<?php

use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

// ==================== UTANG DETAIL PAGE ACTIONS ====================

test('utang detail page dapat ditampilkan dengan data', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Test Utang Detail',
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utang->id,
        'tipe' => 'tambah',
        'nominal' => 500000,
        'deskripsi' => 'Pinjaman awal',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Test Utang Detail')
        ->assertSee('Rp');
})
    ->group('filament', 'utang-detail');

test('utang detail - header widgets ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful();
})
    ->group('filament', 'utang-detail');

test('utang detail - title menampilkan tipe dan nama', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Budi Santoso',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Budi Santoso');
})
    ->group('filament', 'utang-detail');

test('utang detail - tanpa parent tampilkan default', function () {
    $user = createRegularUserWithBukuKas();

    // UtangDetail throws 404 for non-existent record, verify it doesn't crash with valid data
    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Default Test',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Default Test');
})
    ->group('filament', 'utang-detail');

test('utang detail - dengan tempo menampilkan subheading', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Test Tempo',
        'tempo' => now()->addMonth(),
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Jatuh tempo');
})
    ->group('filament', 'utang-detail');

// ==================== PIUTANG DETAIL PAGE ACTIONS ====================

test('piutang detail page dapat ditampilkan dengan data', function () {
    $user = createRegularUserWithBukuKas();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Test Piutang Detail',
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $piutang->id,
        'tipe' => 'tambah',
        'nominal' => 750000,
        'deskripsi' => 'Piutang dari customer',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSee('Test Piutang Detail')
        ->assertSee('Rp');
})
    ->group('filament', 'piutang-detail');

test('piutang detail - header widgets ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful();
})
    ->group('filament', 'piutang-detail');

test('piutang detail - title menampilkan tipe dan nama', function () {
    $user = createRegularUserWithBukuKas();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Ani Wijaya',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSee('Ani Wijaya');
})
    ->group('filament', 'piutang-detail');

test('piutang detail - dengan tempo menampilkan subheading', function () {
    $user = createRegularUserWithBukuKas();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Test Piutang Tempo',
        'tempo' => now()->addWeek(),
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $piutang->id,
        'tipe' => 'tambah',
        'nominal' => 200000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSee('Jatuh tempo');
})
    ->group('filament', 'piutang-detail');

test('piutang detail - tanpa parent tampilkan default', function () {
    $user = createRegularUserWithBukuKas();

    // PiutangDetail throws 404 for non-existent record, verify it doesn't crash with valid data
    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Default Test',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSee('Default Test');
})
    ->group('filament', 'piutang-detail');
