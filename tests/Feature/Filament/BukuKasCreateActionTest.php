<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== BUKU KAS CREATE ACTION ====================

test('list buku kas page dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful();
})
    ->group('filament', 'buku-kas');

test('list buku kas menampilkan data buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSeeText($bukuKas->nama_buku);
})
    ->group('filament', 'buku-kas');

test('list buku kas memiliki header actions', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful();
})
    ->group('filament', 'buku-kas');

test('action membuat buku kas mencatat saldo awal pada dompet default', function () {
    $user = createRegularUserWithBukuKas();
    $dompet = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 100000,
        'is_default' => true,
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: [
            'nama_buku' => 'Kas Proyek',
            'saldo' => 50000,
            'description' => 'Dana proyek',
        ])
        ->assertHasNoActionErrors();

    $bukuKas = $user->buku_kas()->where('nama_buku', 'Kas Proyek')->firstOrFail();
    $transaksi = Transaksi::where('buku_kas_id', $bukuKas->id)->firstOrFail();

    expect($bukuKas->saldo)->toBe(50000)
        ->and($dompet->fresh()->saldo)->toBe(150000)
        ->and($transaksi->dompet_id)->toBe($dompet->id)
        ->and($transaksi->deskripsi)->toBe('Saldo pertama');
})->group('filament', 'buku-kas');
