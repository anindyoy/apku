<?php

use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;

// ==================== TRANSACSI OBSERVER ====================

test('transaksi observer - created pemasukan menambah saldo buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $saldoAwal = $bukuKas->saldo;

    $jenis = \App\Models\JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    // Use explicit different timestamps to trigger the observer
    // The observer only fires when created_at != buku_kas.created_at
    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test pemasukan',
    ]);

    $bukuKas->refresh();
    $this->assertEquals($saldoAwal + 50000, $bukuKas->saldo);
})
    ->group('models', 'observers');

test('transaksi observer - created pengeluaran mengurangi saldo buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $saldoAwal = $bukuKas->saldo;

    $jenis = \App\Models\JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pengeluaran',
    ]);

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 30000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test pengeluaran',
    ]);

    $bukuKas->refresh();
    $this->assertEquals($saldoAwal - 30000, $bukuKas->saldo);
})
    ->group('models', 'observers');

test('transaksi observer - created transfer pengeluaran mengurangi saldo', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $saldoAwal = $bukuKas->saldo;

    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 25000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'deskripsi' => 'Test transfer pengeluaran',
    ]);

    $bukuKas->refresh();
    $this->assertEquals($saldoAwal - 25000, $bukuKas->saldo);
})
    ->group('models', 'observers');

test('transaksi observer - updated pemasukan mengubah saldo sesuai nominal baru', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = \App\Models\JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test update pemasukan',
    ]);

    $saldoSetelahCreate = $bukuKas->fresh()->saldo;

    // Update nominal - observer adjusts the difference
    $transaksi->update(['nominal' => 150000]);

    $bukuKas->refresh();
    $this->assertEquals($saldoSetelahCreate - 100000 + 150000, $bukuKas->saldo);
})
    ->group('models', 'observers');

test('transaksi observer - updated pengeluaran mengubah saldo sesuai nominal baru', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = \App\Models\JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pengeluaran',
    ]);

    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 50000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test update pengeluaran',
    ]);

    $saldoSetelahCreate = $bukuKas->fresh()->saldo;

    // Update nominal - observer adjusts the difference
    $transaksi->update(['nominal' => 80000]);

    $bukuKas->refresh();
    $this->assertEquals($saldoSetelahCreate + 50000 - 80000, $bukuKas->saldo);
})
    ->group('models', 'observers');

test('transaksi observer - deleted tidak mengubah saldo', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $saldoAwal = $bukuKas->saldo;

    $jenis = \App\Models\JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'tanggal' => now()->subDay(),
        'created_at' => now()->subHour(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Test delete',
    ]);

    $saldoSetelahCreate = $bukuKas->fresh()->saldo;

    // Delete should not change saldo (deleted method is empty)
    $transaksi->delete();

    $bukuKas->refresh();
    $this->assertEquals($saldoSetelahCreate, $bukuKas->saldo);
})
    ->group('models', 'observers');

// ==================== UTANG PIUTANG OBSERVER ====================

test('utang piutang observer - deleted cascade menghapus detail', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    // Create detail records
    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'kurang',
        'nominal' => 50000,
    ]);

    $this->assertEquals(2, UtangPiutangDetail::where('utang_piutang_id', $utangPiutang->id)->count());

    $utangPiutang->delete();

    $this->assertEquals(0, UtangPiutangDetail::where('utang_piutang_id', $utangPiutang->id)->count());
})
    ->group('models', 'observers');

test('utang piutang observer - created event tidak error', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'piutang',
    ]);

    $this->assertDatabaseHas('utang_piutang', [
        'id' => $utangPiutang->id,
        'tipe' => 'piutang',
    ]);
})
    ->group('models', 'observers');

// ==================== OBSERVER STUB METHODS (restored / forceDeleted) ====================

test('transaksi observer - restored method dapat dipanggil', function () {
    $observer = new \App\Observers\TransaksiObserver();
    $transaksi = Transaksi::factory()->make();

    $result = $observer->restored($transaksi);
    $this->assertNull($result);
})->group('models', 'observers');

test('transaksi observer - forceDeleted method dapat dipanggil', function () {
    $observer = new \App\Observers\TransaksiObserver();
    $transaksi = Transaksi::factory()->make();

    $result = $observer->forceDeleted($transaksi);
    $this->assertNull($result);
})->group('models', 'observers');

test('utang piutang observer - restored method dapat dipanggil', function () {
    $observer = new \App\Observers\UtangPiutangObserver();
    $utangPiutang = UtangPiutang::factory()->make();

    $result = $observer->restored($utangPiutang);
    $this->assertNull($result);
})->group('models', 'observers');

test('utang piutang observer - forceDeleted method dapat dipanggil', function () {
    $observer = new \App\Observers\UtangPiutangObserver();
    $utangPiutang = UtangPiutang::factory()->make();

    $result = $observer->forceDeleted($utangPiutang);
    $this->assertNull($result);
})->group('models', 'observers');
