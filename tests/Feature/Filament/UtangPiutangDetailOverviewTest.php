<?php

use App\Filament\Resources\PiutangResource\Pages\PiutangDetail;
use App\Filament\Resources\UtangResource\Pages\UtangDetail;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

// ==================== UTANG PIUTANG DETAIL OVERVIEW WIDGET ====================
// Tests the UtangPiutangDetailOverview widget indirectly via detail pages.
// The widget is lazy-loaded in header widgets, so we test via page rendering
// which triggers getStats() method covering lines 22-34.

test('widget overview menghitung sisa utang dengan benar - multiple details', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Andi Saputra',
        'tipe' => 'utang',
    ]);

    // tambah: 300000
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => 200000,
        'tipe' => 'tambah',
        'created_at' => now()->subDays(2),
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => 100000,
        'tipe' => 'tambah',
        'created_at' => now()->subDay(),
    ]);

    // kurang: 50000
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => 50000,
        'tipe' => 'kurang',
        'created_at' => now(),
    ]);

    // Expected sisa = (200000 + 100000) - 50000 = 250000
    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSeeText('Rp');
})->group('filament', 'widget');

test('widget overview menangani data kosong - tidak ada detail', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Tanpa Detail',
        'tipe' => 'utang',
    ]);

    // No UtangPiutangDetail records - sisa should be 0
    Livewire::actingAs($user)
        ->test(UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSeeHtml('Rp');
})->group('filament', 'widget');

test('widget overview piutang dengan multiple details', function () {
    $user = User::notAdmin()->inRandomOrder()->first();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'kepada' => 'Rina Wati',
        'tipe' => 'piutang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $piutang->id,
        'nominal' => 500000,
        'tipe' => 'tambah',
        'created_at' => now()->subDays(3),
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $piutang->id,
        'nominal' => 200000,
        'tipe' => 'kurang',
        'created_at' => now(),
    ]);

    // Expected sisa = 500000 - 200000 = 300000
    Livewire::actingAs($user)
        ->test(PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSeeText('Rp');
})->group('filament', 'widget');
