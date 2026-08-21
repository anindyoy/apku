<?php

use App\Models\JenisTransaksi;
use App\Models\UtangPiutang;
use Livewire\Livewire;

// ==================== UTANG RESOURCE ====================

test('utang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();

    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Utang Test']);

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
        'nominal' => 300000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');

test('utang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();

    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Utang Test']);

    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
        'nominal' => 300000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang])
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');
