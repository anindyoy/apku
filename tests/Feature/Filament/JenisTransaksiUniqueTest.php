<?php

use App\Models\JenisTransaksi;
use Illuminate\Database\QueryException;

test('pengguna tidak dapat membuat jenis transaksi dengan nama dan tipe yang sama', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Penjualan',
        'tipe' => 'Pemasukan',
    ]);

    expect(fn () => JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Penjualan',
        'tipe' => 'Pemasukan',
    ]))->toThrow(QueryException::class);
});

test('pengguna dapat memakai nama jenis transaksi yang sama untuk tipe berbeda', function () {
    $user = createRegularUserWithBukuKas();

    JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Transfer Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Transfer Uji Unik',
        'tipe' => 'Pengeluaran',
    ]);

    expect(JenisTransaksi::where('nama_jenis', 'Transfer Uji Unik')->count())->toBe(2);
});

test('pengguna berbeda dapat memakai nama dan tipe jenis transaksi yang sama', function () {
    $userPertama = createRegularUserWithBukuKas();
    $userKedua = createRegularUserWithBukuKas();

    JenisTransaksi::factory()->create([
        'user_id' => $userPertama->id,
        'nama_jenis' => 'Komisi Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    JenisTransaksi::factory()->create([
        'user_id' => $userKedua->id,
        'nama_jenis' => 'Komisi Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    expect(JenisTransaksi::withoutGlobalScopes()->where('nama_jenis', 'Komisi Uji Unik')->count())->toBe(2);
});
