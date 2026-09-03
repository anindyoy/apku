<?php

use App\Filament\Pages\PencarianTransaksi;
use App\Models\BukuKas;
use App\Models\Dompet;
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

test('pencarian global dapat memfilter dompet dan buku kas secara bersamaan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuPertama = $user->buku_kas()->firstOrFail();
    $bukuKedua = BukuKas::factory()->create(['user_id' => $user->id]);
    $cash = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $bank = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $kategori = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pendapatan',
        'tipe' => 'Pemasukan',
    ]);

    $sesuai = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuPertama->id,
        'dompet_id' => $cash->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Pencarian gabungan sesuai',
    ]);
    $bedaDompet = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuPertama->id,
        'dompet_id' => $bank->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Pencarian gabungan beda dompet',
    ]);
    $bedaBuku = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKedua->id,
        'dompet_id' => $cash->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Pencarian gabungan beda buku',
    ]);

    Livewire::actingAs($user)
        ->test(PencarianTransaksi::class)
        ->searchTable('Pencarian gabungan')
        ->filterTable('buku_kas_id', $bukuPertama->id)
        ->filterTable('dompet_id', $cash->id)
        ->assertCanSeeTableRecords([$sesuai])
        ->assertCanNotSeeTableRecords([$bedaDompet, $bedaBuku]);
})->group('filament', 'pencarian-transaksi');

test('pencarian global tetap menampilkan nama dompet yang sudah dihapus', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $dompet = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Dompet Lama', 'saldo' => 0]);
    $kategori = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Arsip',
        'tipe' => 'Pengeluaran',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompet->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => 'Pengeluaran',
        'deskripsi' => 'Transaksi dompet lama',
    ]);
    $dompet->delete();

    Livewire::actingAs($user)
        ->test(PencarianTransaksi::class)
        ->searchTable('Transaksi dompet lama')
        ->assertCanSeeTableRecords([$transaksi])
        ->assertTableColumnStateSet('dompet.nama_dompet', 'Dompet Lama', $transaksi);
})->group('filament', 'pencarian-transaksi');

test('filter pencarian dompet tidak menerima dompet pengguna lain', function () {
    $user = createRegularUserWithBukuKas();
    $penggunaLain = createRegularUserWithBukuKas();
    $dompet = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $dompetLain = Dompet::withoutGlobalScopes()->create([
        'user_id' => $penggunaLain->id,
        'nama_dompet' => 'Cash',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'dompet_id' => $dompet->id,
        'jenis' => 'Pemasukan',
        'deskripsi' => 'Transaksi aman milik sendiri',
    ]);

    Livewire::actingAs($user)
        ->test(PencarianTransaksi::class)
        ->searchTable('Transaksi aman')
        ->filterTable('dompet_id', $dompetLain->id)
        ->assertCanNotSeeTableRecords([$transaksi]);
})->group('filament', 'pencarian-transaksi');
