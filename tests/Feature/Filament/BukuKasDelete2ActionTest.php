<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

test('buku kas dengan transaksi menampilkan tombol hapus', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenis = JenisTransaksi::where('tipe', 'Pemasukan')->first();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test transaksi untuk delete action',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful()
        ->assertSeeText('Hapus');
})->group('filament', 'buku-kas-delete2');

test('buku kas tanpa transaksi tidak menampilkan tombol hapus', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->assertSuccessful();
})->group('filament', 'buku-kas-delete2');

test('transaksi dan saldo dipindahkan sebelum buku kas dihapus', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $bukuKas->update(['saldo' => 50000]);
    $jenis = JenisTransaksi::where('tipe', 'Pemasukan')->first();

    $secondBukuKas = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan',
        'saldo' => 25000,
    ]);

    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi untuk test delete2 form',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callTableAction('hapusDanPindahkan', $bukuKas, data: [
            'buku_kas_id' => $secondBukuKas->id,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseMissing('buku_kas', ['id' => $bukuKas->id]);
    $this->assertDatabaseHas('transaksi', [
        'id' => $transaksi->id,
        'buku_kas_id' => $secondBukuKas->id,
    ]);
    expect($secondBukuKas->fresh()->saldo)->toBe(75000);
})->group('filament', 'buku-kas-delete2');

test('buku kas tujuan wajib milik pengguna yang sama', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenis = JenisTransaksi::where('tipe', 'Pemasukan')->first();
    $userLain = createRegularUserWithBukuKas();
    $bukuKasUserLain = $userLain->buku_kas()->first();

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi tetap aman',
    ]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callTableAction('hapusDanPindahkan', $bukuKas, data: [
            'buku_kas_id' => $bukuKasUserLain->id,
        ])
        ->assertHasTableActionErrors(['buku_kas_id']);

    $this->assertDatabaseHas('buku_kas', ['id' => $bukuKas->id]);
    $this->assertDatabaseHas('transaksi', ['buku_kas_id' => $bukuKas->id]);
});
