<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
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
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_sebelumnya = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest()
        ->first();

    expect($bukuKas->saldo)
        ->toBe($transaksi_sebelumnya->saldo_akhir);
})->group('create_transaksi');

test('tambah data transaksi pemasukan pada tanggal 5 bulan ini', function () {
    $user_id = 2;
    $user = User::find($user_id);

    $bukuKas = BukuKas::where('user_id', $user->id)->first();

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    $nominal = rand(1, 10) . '0';
    $tanggal = now()->startOfMonth()->addDays(2); // Tanggal 5 bulan ini

    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
            'tanggal' => $tanggal,
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $transaksi_sebelumnya = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest()
        ->first();

    try {
        expect($bukuKas->saldo)
            ->toBe($transaksi_sebelumnya->saldo_akhir);
    } catch (\Throwable $th) {
        dd(
            $th->getMessage(),
            $nominal,
            $tanggal,
            $transaksi_sebelumnya->saldo_akhir,
            $bukuKas->saldo
        );
    }
})->group('create_transaksi');
