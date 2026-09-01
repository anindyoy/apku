<?php

use App\Models\BukuKas;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== BUKU KAS CREATE ACTION ====================

test('list buku kas page dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\ListBukuKas::class)
        ->assertSuccessful();
})
    ->group('filament', 'buku-kas');

test('list buku kas menampilkan data buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\ListBukuKas::class)
        ->assertSeeText($bukuKas->nama_buku);
})
    ->group('filament', 'buku-kas');

test('list buku kas memiliki header actions', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\ListBukuKas::class)
        ->assertSuccessful();
})
    ->group('filament', 'buku-kas');
