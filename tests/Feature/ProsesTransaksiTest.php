<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use App\Filament\Pages\DataTransaksi;

test('buat tambah pemasukan pada tanggal saat ini', function () {
    $user_id = 2;
    $user = User::find($user_id);

    $bukuKas = BukuKas::where('user_id', $user->id)->first();
    $transaksi_sebelumnya = Transaksi::where('user_id', $user_id)
        ->where('buku_kas_id', $bukuKas->id)
        ->latest()
        ->first();

    $jenisTransaksi = JenisTransaksi::where('user_id', $user->id)
        ->where('tipe', 'Pemasukan')
        ->inRandomOrder()
        ->first();

    $nominal = rand(10, 100);

    Livewire::actingAs($user)
        ->test(DataTransaksi::class)
        ->callTableAction('Catat Pemasukan', data: [
            'jenis_transaksi_id' => $jenisTransaksi->id,
            'nominal' => $nominal,
        ])
        ->assertHasNoErrors();

    $bukuKas->refresh();
    expect($bukuKas->saldo)
        ->toBe($transaksi_sebelumnya->saldo_akhir + $nominal);
});
