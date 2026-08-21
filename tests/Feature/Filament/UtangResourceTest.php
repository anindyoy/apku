<?php

use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

// ==================== UTANG RESOURCE ====================

test('utang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Test Utang',
    ]);

    // Buat detail untuk nominal (nominal dihitung dari utang_piutang_detail)
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $utang->id,
        'nominal' => 300000,
        'tipe' => 'tambah',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');

test('utang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
        'kepada' => 'Test Utang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang->code])
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');
