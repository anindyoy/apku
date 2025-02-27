<?php

use Carbon\Carbon;
use App\Models\User;
use Livewire\Livewire;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\DataTransaksi;

test('tambah data transaksi pemasukan pada tanggal saat ini', function () {
    $user_id = 2;
    $user = User::find($user_id);

    $bukuKas = BukuKas::where('user_id', $user->id)->first();

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    $nominal = rand(1, 10) . '0';

    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
            'deskripsi' => 'testing'
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest()
        ->first();

    expect($bukuKas->saldo)
        ->toBe($transaksi_terakhir->saldo_akhir);
})->group('create_pemasukan_now');

test('tambah data transaksi pemasukan ke tengah tanggal transaksi yang sudah ada', function () {
    $user_id = 2;
    $user = User::find($user_id);

    $bukuKas = BukuKas::where('user_id', $user->id)->first();

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    $nominal = rand(1, 10) . '0';
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->orderBy('tanggal', 'desc')
        ->skip(rand(2, 5))
        ->first();

    $tanggal = Carbon::parse($transaksi_terakhir->tanggal)->addMinutes(10);

    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
            'tanggal' => $tanggal,
            'deskripsi' => 'testing'
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest('tanggal')
        ->first();

    expect($transaksi_terakhir->saldo_akhir)
        ->toBe($bukuKas->saldo);
})->group('create_pemasukan_past');

test('hapus data transaksi pemasukan', function () {
    $user_id = 2;
    $user = User::find($user_id);
    $bukuKas = BukuKas::where('user_id', $user->id)->first();
    $nominal = rand(1, 10) . '0';

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    // Create a transaction to delete/*  */
    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest('tanggal')
        ->first();

    // Delete the transaction
    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction(DeleteAction::class, $transaksi_terakhir)
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest('tanggal')
        ->first();

    expect($bukuKas->saldo)
        ->toBe($transaksi_terakhir->saldo_akhir);
})->group('delete_pemasukan_now');

test('hapus data transaksi pemasukan yang ada di tengah tanggal transaksi yang sudah ada', function () {
    $user_id = 2;
    $user = User::find($user_id);

    $bukuKas = BukuKas::where('user_id', $user->id)->first();

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    $nominal = rand(1, 10) . '0';
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->orderBy('tanggal', 'desc')
        ->skip(rand(2, 5))
        ->first();

    $tanggal = Carbon::parse($transaksi_terakhir->tanggal)->addMinutes(10);

    // Create a transaction to delete/*  */
    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
            'tanggal' => $tanggal,
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest('tanggal')
        ->first();

    // Delete the transaction
    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction(DeleteAction::class, $transaksi_terakhir)
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest('tanggal')
        ->first();

    expect($bukuKas->saldo)
        ->toBe($transaksi_terakhir->saldo_akhir);
})->group('delete_pemasukan_now');
