<?php

use App\Models\BukuKas;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== TRANSAKSI LIST PAGE + DELETE/EDIT ACTION CALLBACKS ====================

// Note: EditTransaksi page is commented out in TransaksiResource::getPages()
// so we test the list page which contains the delete/edit action callbacks.
// ListTransaksis filters data by active tab (buku_kas), so we cannot easily
// assert specific data entries without controlling the tab state.

test('transaksi list - page dapat ditampilkan dengan data pemasukan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenis = \App\Models\JenisTransaksi::where('tipe', 'Pemasukan')->first();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now()->subDay(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test pemasukan',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi-delete');

test('transaksi list - page dapat ditampilkan dengan data pengeluaran', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenis = \App\Models\JenisTransaksi::where('tipe', 'Pengeluaran')->first();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 25000,
        'tanggal' => now()->subDay(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test pengeluaran',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi-delete');

test('transaksi list - page dapat ditampilkan dengan data transfer', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $bukuKas2 = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Target',
        'saldo' => 0,
    ]);

    $transferCode = uniqid();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 100000,
        'tanggal' => now()->subDay(),
        'transfer_code' => $transferCode,
        'tujuan_buku_tabungan_id' => $bukuKas2->id,
        'deskripsi' => 'Transfer keluar',
    ]);

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Transfer Pemasukan',
        'nominal' => 100000,
        'tanggal' => now()->subDay(),
        'transfer_code' => $transferCode,
        'asal_buku_tabungan_id' => $bukuKas->id,
        'deskripsi' => 'Transfer masuk',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi-delete');

test('list transaksi - transaksi exist di database', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $bukuKas2 = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Transfer',
        'saldo' => 0,
    ]);

    $transferCode = uniqid();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 75000,
        'tanggal' => now()->subDay(),
        'transfer_code' => $transferCode,
        'tujuan_buku_tabungan_id' => $bukuKas2->id,
        'deskripsi' => 'Transfer test',
    ]);

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Transfer Pemasukan',
        'nominal' => 75000,
        'tanggal' => now()->subDay(),
        'transfer_code' => $transferCode,
        'asal_buku_tabungan_id' => $bukuKas->id,
        'deskripsi' => 'Transfer terima',
    ]);

    // Verify data exists in database
    $this->assertDatabaseHas('transaksi', [
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 75000,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi-delete');

test('transaksi list - header actions tersedia', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class, [
            'filterBukuKas' => (string) $bukuKas->id,
        ])
        ->assertSuccessful()
        ->assertSeeText('Aksi transaksi')
        ->assertSeeText('Transfer saldo')
        ->assertSeeText('Catat pemasukan')
        ->assertSeeText('Catat pengeluaran');
})
    ->group('filament', 'transaksi-delete');
