<?php

use App\Models\JenisTransaksi;
use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== PIUTANG RESOURCE ====================

test('piutang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Piutang Test']);

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'piutang',
        'nama' => 'Test Piutang',
        'nominal' => 500000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\ListPiutangs::class)
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');

test('piutang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();

    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Piutang Test']);

    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'piutang',
        'nama' => 'Test Piutang',
        'nominal' => 500000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang])
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');
