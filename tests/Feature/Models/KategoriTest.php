<?php

use App\Models\Kategori;
use App\Models\User;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\UserSeeder;

// ==================== MODEL KATEGORI ====================

beforeEach(function () {
    $this->seed([
        UserSeeder::class,
        KategoriSeeder::class,
    ]);

    // Autentikasi pengguna untuk metode yang memanggil auth()->user()->isAdmin().
    $user = User::first();
    $this->actingAs($user);
});

test('kategori memiliki relasi user', function () {
    $jenis = Kategori::first();

    expect($jenis->user)->not->toBeNull();
    expect($jenis->user)->toBeInstanceOf(User::class);
});

test('kategori memiliki relasi transaksi', function () {
    $jenis = Kategori::first();

    expect($jenis->transaksi())->not->toBeNull();
});

test('kategori form mengembalikan array schema', function () {
    $form = Kategori::form('Pemasukan');

    expect($form)->toBeArray();
    expect($form)->toHaveCount(1);
    expect($form[0]->getName())->toBe('nama');
});

test('kategori columns mengembalikan array columns', function () {
    $columns = Kategori::columns();

    expect($columns)->toBeArray();
    expect($columns)->toHaveCount(1);
    expect($columns[0]->getName())->toBe('nama');
});

test('daftar kategori menampilkan jumlah transaksi di bawah nama', function (string $component) {
    $this->actingAs(User::query()->notAdmin()->firstOrFail());
    $page = \Livewire\Livewire::test($component)->assertSuccessful();
    $table = $page->instance()->getTable();
    expect(array_keys($table->getColumns()))->toBe(['nama']);
    $records = $page->instance()->getTableRecords();
    expect($records->count())->toBeGreaterThan(0);
    foreach ($records as $record) {
        $column = $table->getColumn('nama')->record($record);
        expect($record->transaksi_count)->toBe($record->transaksi()->count())
            ->and($column->getState())->toBe($record->nama)
            ->and($column->getDescriptionBelow())->toBe(number_format($record->transaksi()->count(), 0, ',', '.').' transaksi');
    }
})->with([
    'pemasukan' => [\App\Livewire\Kategori\Pemasukan::class],
    'pengeluaran' => [\App\Livewire\Kategori\Pengeluaran::class],
]);

test('kategori headerActions mengembalikan create action', function () {
    $actions = Kategori::headerActions('Pemasukan');

    expect($actions)->toBeArray();
    expect($actions)->toHaveCount(1);
});

test('kategori actions mengembalikan edit dan delete action', function () {
    $actions = Kategori::actions('Pemasukan');

    expect($actions)->toBeArray();
    expect($actions)->toHaveCount(3); // EditAction, DeleteAction, Hapus action
});

test('kategori menggunakan table yang benar', function () {
    $model = new Kategori;

    expect($model->getTable())->toBe('kategori');
});

test('kategori memiliki guarded kosong', function () {
    $model = new Kategori;

    expect($model->getGuarded())->toBe([]);
});
