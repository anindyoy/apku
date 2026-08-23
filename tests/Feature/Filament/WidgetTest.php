<?php

use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== WIDGETS ====================

test('widget utang piutang detail dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utangPiutang->code])
        ->assertSuccessful();
})
    ->group('filament', 'widgets');
