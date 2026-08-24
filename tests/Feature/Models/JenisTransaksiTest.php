<?php

use App\Models\JenisTransaksi;
use App\Models\User;

// ==================== JENISTRANSAKSI MODEL ====================

beforeEach(function () {
    $this->seed([
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\JenisTransaksiSeeder::class,
    ]);

    // Authenticate user for methods that call auth()->user()->isSuper()
    $user = \App\Models\User::first();
    $this->actingAs($user);
});

test('jenis transaksi memiliki relasi user', function () {
    $jenis = JenisTransaksi::first();

    expect($jenis->user)->not->toBeNull();
    expect($jenis->user)->toBeInstanceOf(User::class);
});

test('jenis transaksi memiliki relasi transaksi', function () {
    $jenis = JenisTransaksi::first();

    expect($jenis->transaksi())->not->toBeNull();
});

test('jenis transaksi form mengembalikan array schema', function () {
    $form = JenisTransaksi::form();

    expect($form)->toBeArray();
    expect($form)->toHaveCount(1);
    expect($form[0]->getName())->toBe('nama_jenis');
});

test('jenis transaksi columns mengembalikan array columns', function () {
    $columns = JenisTransaksi::columns();

    expect($columns)->toBeArray();
    expect($columns)->toHaveCount(2);
    expect($columns[0]->getName())->toBe('nama_jenis');
    expect($columns[1]->getName())->toBe('transaksi_count');
});

test('jenis transaksi headerActions mengembalikan create action', function () {
    $actions = JenisTransaksi::headerActions('Pemasukan');

    expect($actions)->toBeArray();
    expect($actions)->toHaveCount(1);
});

test('jenis transaksi actions mengembalikan edit dan delete action', function () {
    $actions = JenisTransaksi::actions('Pemasukan');

    expect($actions)->toBeArray();
    expect($actions)->toHaveCount(3); // EditAction, DeleteAction, Hapus action
});

test('jenis transaksi menggunakan table yang benar', function () {
    $model = new JenisTransaksi();

    expect($model->getTable())->toBe('jenis_transaksi');
});

test('jenis transaksi memiliki guarded kosong', function () {
    $model = new JenisTransaksi();

    expect($model->getGuarded())->toBe([]);
});
