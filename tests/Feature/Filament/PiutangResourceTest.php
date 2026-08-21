<?php

use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== PIUTANG RESOURCE ====================

test('piutang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Test Piutang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\ListPiutangs::class)
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');

test('piutang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
        'kepada' => 'Test Piutang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang->code])
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');
