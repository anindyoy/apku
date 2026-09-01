<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Transaksi;
use Livewire\Livewire;

test('masa aktif hari ini masih berlaku sedangkan null dan tanggal lalu tidak berlaku', function () {
    $user = createRegularUserWithBukuKas();

    $user->update(['masa_aktif' => today()]);
    expect($user->fresh()->masaAktifBerlaku())->toBeTrue();

    $user->update(['masa_aktif' => today()->subDay()]);
    expect($user->fresh()->masaAktifBerlaku())->toBeFalse();

    $user->update(['masa_aktif' => null]);
    expect($user->fresh()->masaAktifBerlaku())->toBeFalse();
})->group('filament', 'masa-aktif');

test('user tanpa masa aktif dapat memiliki satu buku tambahan selain kas utama', function () {
    $user = createRegularUserWithBukuKas();
    $user->update(['masa_aktif' => null]);
    $user->buku_kas()->firstOrFail()->update(['nama_buku' => 'Kas Utama']);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertActionVisible('create');

    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tambahan Gratis',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertActionHidden('create');
})->group('filament', 'masa-aktif');

test('user kedaluwarsa dapat mengelola kas utama dan satu buku tambahan pertama', function () {
    $user = createRegularUserWithBukuKas();
    $user->update(['masa_aktif' => today()->subDay()]);

    $kasUtama = $user->buku_kas()->firstOrFail();
    $kasUtama->update(['nama_buku' => 'Kas Utama']);
    $kasTambahanGratis = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tambahan Gratis',
    ]);
    $kasTambahanBerbayar = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tambahan Berbayar',
    ]);

    $transaksiUtama = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasUtama->id,
        'tanggal' => now(),
    ]);
    $transaksiTambahanGratis = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasTambahanGratis->id,
        'tanggal' => now(),
    ]);
    $transaksiTambahanBerbayar = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasTambahanBerbayar->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $kasTambahanGratis->id])
        ->assertActionVisible('Transfer saldo')
        ->assertActionVisible('Catat Pemasukan')
        ->assertActionVisible('Catat Pengeluaran')
        ->assertTableActionVisible('edit', $transaksiTambahanGratis)
        ->assertTableActionVisible('delete', $transaksiTambahanGratis);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $kasTambahanBerbayar->id])
        ->assertActionHidden('Transfer saldo')
        ->assertActionHidden('Catat Pemasukan')
        ->assertActionHidden('Catat Pengeluaran')
        ->assertTableActionHidden('edit', $transaksiTambahanBerbayar)
        ->assertTableActionHidden('delete', $transaksiTambahanBerbayar)
        ->assertCanSeeTableRecords([$transaksiTambahanBerbayar]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $kasUtama->id])
        ->assertActionVisible('Catat Pemasukan')
        ->assertActionVisible('Catat Pengeluaran')
        ->assertTableActionVisible('edit', $transaksiUtama)
        ->assertTableActionVisible('delete', $transaksiUtama);
})->group('filament', 'masa-aktif');

test('user dengan masa aktif dapat membuat buku dan mengelola transaksi kas lain', function () {
    $user = createRegularUserWithBukuKas();
    $user->update(['masa_aktif' => today()->addDay()]);
    $kasLain = $user->buku_kas()->firstOrFail();
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasLain->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertActionVisible('create');

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, ['filterBukuKas' => (string) $kasLain->id])
        ->assertActionVisible('Transfer saldo')
        ->assertActionVisible('Catat Pemasukan')
        ->assertActionVisible('Catat Pengeluaran')
        ->assertTableActionVisible('edit', $transaksi)
        ->assertTableActionVisible('delete', $transaksi);
})->group('filament', 'masa-aktif');
