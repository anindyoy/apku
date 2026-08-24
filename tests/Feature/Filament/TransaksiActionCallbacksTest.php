<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;

// ==================== TRANSAKSI RESOURCE - DELETE ACTION CALLBACKS ====================
// These tests target the uncovered lines in TransaksiResource.php:
// - Lines 152-188: DeleteAction after callback for transfer/pengeluaran/pemasukan
// Note: Lines 94-96 (searchable callback) and 126-148 (edit before callback)
// are not testable via Livewire test helper due to Filament v4 limitations.

// Helper to create a full transaksi test setup
function createTransaksiTestData(string $jenis = 'Pemasukan'): array
{
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenisTransaksi = JenisTransaksi::where('tipe', $jenis === 'Pengeluaran' ? 'Pengeluaran' : 'Pemasukan')->first();

    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => $jenis,
        'nominal' => 50000,
        'tanggal' => now()->subDay(),
        'jenis_transaksi_id' => $jenisTransaksi->id,
        'deskripsi' => 'Test transaksi untuk callback',
    ]);

    return compact('user', 'bukuKas', 'transaksi', 'jenisTransaksi');
}

// --- Test DeleteAction after callback for Pengeluaran (lines 179-186) ---

test('transaksi delete - pengeluaran mengembalikan saldo buku kas', function () {
    $data = createTransaksiTestData('Pengeluaran');
    $user = $data['user'];
    $bukuKas = $data['bukuKas'];
    $transaksi = $data['transaksi'];

    $saldoAwal = $bukuKas->fresh()->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->callTableAction('delete', $transaksi);

    // After deleting pengeluaran, saldo should increase by the nominal
    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKas->id,
        'saldo' => $saldoAwal + $transaksi->nominal,
    ]);
})->group('filament', 'transaksi-callback');

// --- Test DeleteAction after callback for Pemasukan (lines 179-186) ---

test('transaksi delete - pemasukan mengurangi saldo buku kas', function () {
    $data = createTransaksiTestData('Pemasukan');
    $user = $data['user'];
    $bukuKas = $data['bukuKas'];
    $transaksi = $data['transaksi'];

    $saldoAwal = $bukuKas->fresh()->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->callTableAction('delete', $transaksi);

    // After deleting pemasukan, saldo should decrease by the nominal
    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKas->id,
        'saldo' => $saldoAwal - $transaksi->nominal,
    ]);
})->group('filament', 'transaksi-callback');

// --- Test DeleteAction after callback for Transfer Pengeluaran (lines 155-178) ---

test('transaksi delete - transfer pengeluaran mengembalikan saldo dan hapus related', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKasAsal = $user->buku_kas()->first();
    $bukuKasTujuan = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tujuan Transfer',
        'saldo' => 0,
    ]);

    $transferCode = uniqid('transfer_');

    // Transfer Pengeluaran from bukuKasAsal
    $transaksiOut = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKasAsal->id,
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 30000,
        'tanggal' => now(),
        'transfer_code' => $transferCode,
        'tujuan_buku_tabungan_id' => $bukuKasTujuan->id,
        'deskripsi' => 'Transfer keluar test',
    ]);

    // Transfer Pemasukan to bukuKasTujuan
    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKasTujuan->id,
        'jenis' => 'Transfer Pemasukan',
        'nominal' => 30000,
        'tanggal' => now(),
        'transfer_code' => $transferCode,
        'asal_buku_tabungan_id' => $bukuKasAsal->id,
        'deskripsi' => 'Transfer masuk test',
    ]);

    $saldoAsalAwal = $bukuKasAsal->fresh()->saldo;
    $saldoTujuanAwal = $bukuKasTujuan->fresh()->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->callTableAction('delete', $transaksiOut);

    // After deleting Transfer Pengeluaran:
    // - bukuKasAsal saldo should increase by 30000 (line 159)
    // - related Transfer Pemasukan should be deleted (line 177)
    // - bukuKasTujuan saldo should decrease by 30000 (line 174)
    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKasAsal->id,
        'saldo' => $saldoAsalAwal + 30000,
    ]);

    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKasTujuan->id,
        'saldo' => $saldoTujuanAwal - 30000,
    ]);

    // Related transaction should be deleted
    $this->assertDatabaseMissing('transaksi', [
        'transfer_code' => $transferCode,
        'jenis' => 'Transfer Pemasukan',
    ]);
})->group('filament', 'transaksi-callback');

// --- Test DeleteAction after callback for Transfer Pemasukan (lines 155-178) ---

test('transaksi delete - transfer pemasukan mengurangi saldo dan hapus related', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKasAsal = $user->buku_kas()->first();
    $bukuKasTujuan = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Penerima Transfer',
        'saldo' => 0,
    ]);

    $transferCode = uniqid('transfer2_');

    // Transfer Pengeluaran from bukuKasAsal
    $transaksiOut = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKasAsal->id,
        'jenis' => 'Transfer Pengeluaran',
        'nominal' => 40000,
        'tanggal' => now(),
        'transfer_code' => $transferCode,
        'tujuan_buku_tabungan_id' => $bukuKasTujuan->id,
        'deskripsi' => 'Transfer keluar',
    ]);

    // Transfer Pemasukan to bukuKasTujuan (this is the one we'll delete)
    $transaksiIn = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKasTujuan->id,
        'jenis' => 'Transfer Pemasukan',
        'nominal' => 40000,
        'tanggal' => now(),
        'transfer_code' => $transferCode,
        'asal_buku_tabungan_id' => $bukuKasAsal->id,
        'deskripsi' => 'Transfer masuk',
    ]);

    $saldoAsalAwal = $bukuKasAsal->fresh()->saldo;
    $saldoTujuanAwal = $bukuKasTujuan->fresh()->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->callTableAction('delete', $transaksiIn);

    // After deleting Transfer Pemasukan:
    // - bukuKasTujuan saldo should decrease by 40000 (line 161)
    // - related Transfer Pengeluaran should be deleted (line 177)
    // - bukuKasAsal saldo should increase by 40000 (line 172)
    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKasTujuan->id,
        'saldo' => $saldoTujuanAwal - 40000,
    ]);

    $this->assertDatabaseHas('buku_kas', [
        'id' => $bukuKasAsal->id,
        'saldo' => $saldoAsalAwal + 40000,
    ]);

    // Related transaction should be deleted
    $this->assertDatabaseMissing('transaksi', [
        'transfer_code' => $transferCode,
        'jenis' => 'Transfer Pengeluaran',
    ]);
})->group('filament', 'transaksi-callback');
