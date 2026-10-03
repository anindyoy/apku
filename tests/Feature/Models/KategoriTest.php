<?php

use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\UserSeeder;

// ==================== MODEL KATEGORI ====================

beforeEach(function () {
    $this->seed([
        UserSeeder::class,
        KategoriSeeder::class,
    ]);

    $this->actingAs(User::query()->notAdmin()->firstOrFail());
});

test('kategori memiliki relasi user dan pembuat', function () {
    $kategori = Kategori::firstOrFail();

    expect($kategori->user)->toBeInstanceOf(User::class)
        ->and($kategori->pembuat)->toBeInstanceOf(User::class)
        ->and($kategori->pembuat->is($kategori->user))->toBeTrue();
});

test('kategori memiliki relasi transaksi dan kas', function () {
    $user = auth()->user();
    $kas = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Kas Relasi']);
    $kategori = Kategori::factory()->untukKas($kas)->create(['user_id' => $user->id, 'nama' => 'Relasi', 'tipe' => 'Pemasukan']);
    $transaksi = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $kas->id,
        'kategori_id' => $kategori->id,
        'jenis' => 'Pemasukan',
        'nominal' => 1000,
        'tanggal' => now(),
    ]);

    expect($kategori->transaksi()->pluck('id')->all())->toBe([$transaksi->id])
        ->and($kategori->kas()->pluck('buku_kas.id')->all())->toBe([$kas->id])
        ->and($kas->kategori()->pluck('kategori.id')->all())->toBe([$kategori->id])
        ->and($kategori->idKas())->toBe([$kas->id])
        ->and($kategori->terhubungKe($kas->id))->toBeTrue()
        ->and($kategori->terhubungKe($kas->id + 1000))->toBeFalse()
        ->and($transaksi->kategori->is($kategori))->toBeTrue();
});

test('daftar kategori menampilkan jumlah transaksi di bawah nama', function () {
    $page = \Livewire\Livewire::test(\App\Filament\Pages\Kategori::class)->assertSuccessful();
    $table = $page->instance()->getTable();
    $records = $page->instance()->getTableRecords();

    expect($records->count())->toBeGreaterThan(0);

    foreach ($records as $record) {
        $column = $table->getColumn('nama')->record($record);
        expect($record->transaksi_count)->toBe($record->transaksi()->count())
            ->and($column->getState())->toBe($record->nama)
            ->and($column->getDescriptionBelow())->toBe(number_format($record->transaksi()->count(), 0, ',', '.').' transaksi');
    }
});

test('kategori menggunakan table yang benar', function () {
    $model = new Kategori;

    expect($model->getTable())->toBe('kategori');
});

test('kategori memiliki guarded kosong', function () {
    $model = new Kategori;

    expect($model->getGuarded())->toBe([]);
});
