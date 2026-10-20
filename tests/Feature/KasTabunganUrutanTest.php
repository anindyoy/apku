<?php

use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\User;

// Memastikan kas biasa diurutkan lebih dulu daripada kas tabungan, dan masing-masing kelompok diurutkan menurut nama.
test('urutan kas biasa sebelum tabungan dan urut nama dalam kelompok', function () {
    $user = User::factory()->create([
        'masa_aktif' => now()->addDays(30),
    ]);

    // Buat kas tabungan bernama Alpha
    $tabunganA = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Alpha Tabungan',
        'saldo' => 100_000,
        'is_tabungan' => true,
    ]);

    // Buat kas biasa bernama Zulu
    $biasaZ = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Zulu Kas',
        'saldo' => 50_000,
        'is_tabungan' => false,
    ]);

    // Buat kas biasa bernama Beta
    $biasaB = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Beta Kas',
        'saldo' => 20_000,
        'is_tabungan' => false,
    ]);

    // Buat kas tabungan bernama Omega
    $tabunganO = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Omega Tabungan',
        'saldo' => 200_000,
        'is_tabungan' => true,
    ]);

    $hasilUrutan = BukuKas::query()
        ->where('user_id', $user->id)
        ->urutanTabungan()
        ->pluck('nama_buku')
        ->all();

    expect($hasilUrutan)->toBe([
        'Beta Kas',
        'Zulu Kas',
        'Alpha Tabungan',
        'Omega Tabungan',
    ]);
});

// Memastikan dropdown opsi buku kas yang dapat dikelola menerapkan urutan tabungan.
test('dropdown opsi buku kas yang dapat dikelola menerapkan urutan tabungan', function () {
    $user = User::factory()->create([
        'masa_aktif' => now()->addDays(30),
    ]);
    $this->actingAs($user);

    BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Tabungan Liburan',
        'saldo' => 500_000,
        'is_tabungan' => true,
    ]);

    BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Warung',
        'saldo' => 300_000,
        'is_tabungan' => false,
    ]);

    BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Harian',
        'saldo' => 100_000,
        'is_tabungan' => false,
    ]);

    $opsi = Transaksi::opsiBukuKasYangDapatDikelola();
    $namaKas = array_values($opsi);

    expect($namaKas)->toBe([
        'Kas Harian',
        'Kas Warung',
        'Tabungan Liburan',
    ]);
});
