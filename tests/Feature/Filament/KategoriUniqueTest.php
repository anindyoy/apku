<?php

use App\Models\Kategori;
use Illuminate\Database\QueryException;

test('pengguna tidak dapat membuat kategori dengan nama dan tipe yang sama', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    Kategori::factory()->create([
        'user_id' => $user->id,
        'nama' => 'Penjualan',
        'tipe' => 'Pemasukan',
    ]);

    expect(fn () => Kategori::factory()->create([
        'user_id' => $user->id,
        'nama' => 'Penjualan',
        'tipe' => 'Pemasukan',
    ]))->toThrow(QueryException::class);
});

test('pengguna dapat memakai nama kategori yang sama untuk tipe berbeda', function () {
    $user = createRegularUserWithBukuKas();

    Kategori::factory()->create([
        'user_id' => $user->id,
        'nama' => 'Transfer Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    Kategori::factory()->create([
        'user_id' => $user->id,
        'nama' => 'Transfer Uji Unik',
        'tipe' => 'Pengeluaran',
    ]);

    expect(Kategori::where('nama', 'Transfer Uji Unik')->count())->toBe(2);
});

test('pengguna berbeda dapat memakai nama dan tipe kategori yang sama', function () {
    $userPertama = createRegularUserWithBukuKas();
    $userKedua = createRegularUserWithBukuKas();

    Kategori::factory()->create([
        'user_id' => $userPertama->id,
        'nama' => 'Komisi Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    Kategori::factory()->create([
        'user_id' => $userKedua->id,
        'nama' => 'Komisi Uji Unik',
        'tipe' => 'Pemasukan',
    ]);

    expect(Kategori::withoutGlobalScopes()->where('nama', 'Komisi Uji Unik')->count())->toBe(2);
});
