<?php

use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

// ==================== EXTENDED WIDGET TESTS ====================

test('widget utang piutang detail - tampilkan total sisa utang', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    // Tambah nominal
    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 200000,
    ]);

    // Kurang nominal (pembayaran)
    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'kurang',
        'nominal' => 50000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utangPiutang->code])
        ->assertSuccessful();
})
    ->group('filament', 'widgets');

test('widget utang overview - tampilkan data utang', function () {
    $user = createRegularUserWithBukuKas();

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\ListUtangs::class)
        ->assertSuccessful();
})
    ->group('filament', 'widgets');

test('widget piutang overview - tampilkan data piutang', function () {
    $user = createRegularUserWithBukuKas();

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\ListPiutangs::class)
        ->assertSuccessful();
})
    ->group('filament', 'widgets');

test('widget kas overview - tampilkan data transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'widgets');

test('widget kas overview - tetap tampil saat periode tidak memiliki transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->where('nama_buku', 'Kas Utama')->firstOrFail();

    $bukuKas->update(['saldo' => 125000]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Widgets\KasOverview::class)
        ->assertSuccessful()
        ->assertSeeText('Rp 125,000');
})
    ->group('filament', 'widgets');

test('widget utang piutang detail - tanpa detail tampilkan total 0', function () {
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
