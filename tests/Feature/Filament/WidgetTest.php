<?php

use App\Models\JenisTransaksi;
use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== WIDGETS ====================

test('widget utang piutang detail dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Test']);

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangPiutangDetail::class, ['record' => $utangPiutang])
        ->assertSuccessful()
        ->assertSee('Total');
})
    ->group('filament', 'widgets');
