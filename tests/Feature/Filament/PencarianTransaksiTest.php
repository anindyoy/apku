<?php

use App\Filament\Pages\PencarianTransaksi;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

test('halaman pencarian global menampilkan transaksi dari seluruh buku kas milik pengguna', function () {
    $user = createRegularUserWithBukuKas();
    $bukuPertama = $user->buku_kas()->first();
    $bukuKedua = BukuKas::factory()->create(['user_id' => $user->id]);
    $kategori = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Transportasi',
        'tipe' => 'Pengeluaran',
    ]);
    $transaksiPertama = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuPertama->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pengeluaran',
        'deskripsi' => 'Tiket kereta antarkota',
    ]);
    $transaksiKedua = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKedua->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pengeluaran',
        'deskripsi' => 'Ongkos taksi',
    ]);

    Livewire::actingAs($user)
        ->test(PencarianTransaksi::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$transaksiPertama, $transaksiKedua])
        ->searchTable('Tiket kereta')
        ->assertCanSeeTableRecords([$transaksiPertama])
        ->assertCanNotSeeTableRecords([$transaksiKedua]);
})->group('filament', 'pencarian-transaksi');

test('pencarian global tidak menampilkan transaksi pengguna lain', function () {
    $user = createRegularUserWithBukuKas();
    $penggunaLain = createRegularUserWithBukuKas();
    $transaksiLain = Transaksi::factory()->create([
        'user_id' => $penggunaLain->id,
        'buku_kas_id' => $penggunaLain->buku_kas()->first()->id,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Data rahasia pengguna lain',
    ]);

    Livewire::actingAs($user)
        ->test(PencarianTransaksi::class)
        ->assertSuccessful()
        ->searchTable('Data rahasia')
        ->assertCanNotSeeTableRecords([$transaksiLain]);
})->group('filament', 'pencarian-transaksi');
