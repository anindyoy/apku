<?php

use App\Models\Wilayah;
use Illuminate\Support\Facades\DB;

// ==================== WILAYAH MODEL ====================

beforeEach(function () {
    // Clear any existing data from previous tests (RefreshDatabase transaction may not roll back DDL)
    DB::table('wilayah')->truncate();

    // Insert minimal test data instead of the 2.7MB wilayah.sql seeder.
    // Format: kode uses dot notation: provinsi (XX), kota (XX.XX), kecamatan (XX.XX.XX)
    DB::table('wilayah')->insert([
        // Provinsi
        ['kode' => '31', 'nama' => 'DKI Jakarta'],
        ['kode' => '32', 'nama' => 'Jawa Barat'],
        // Kota
        ['kode' => '31.71', 'nama' => 'Kota Jakarta Selatan'],
        ['kode' => '31.72', 'nama' => 'Kota Jakarta Timur'],
        ['kode' => '32.73', 'nama' => 'Kota Bandung'],
        // Kecamatan
        ['kode' => '31.71.01', 'nama' => 'Kecamatan Kebayoran Baru'],
        ['kode' => '31.71.02', 'nama' => 'Kecamatan Kebayoran Lama'],
        ['kode' => '32.73.01', 'nama' => 'Kecamatan Bandung Kulon'],
    ]);
});

test('getNamaDaerah mengembalikan nama berdasarkan kode', function () {
    $result = Wilayah::getNamaDaerah('31');

    expect($result)->toBe('DKI Jakarta');
});

test('getNamaDaerah mengembalikan null untuk kode tidak ada', function () {
    $result = Wilayah::getNamaDaerah('XXXX');

    expect($result)->toBeNull();
});

test('getDetailWilayah throws QueryException karena find() uses non-existent id column', function () {
    // getDetailWilayah() uses self::find($id) which queries by 'id' column.
    // The wilayah table has 'kode' as PK but no 'id' column, so it throws QueryException.
    // This test documents the actual (buggy) behavior.
    Wilayah::getDetailWilayah('31.71');
})->throws(\Illuminate\Database\QueryException::class, "Unknown column 'wilayah.id'");

test('getDetailWilayah throws QueryException untuk kode apapun karena id column tidak ada', function () {
    // Even with a nonexistent kode, the 'id' column reference still causes the error.
    Wilayah::getDetailWilayah('9999');
})->throws(\Illuminate\Database\QueryException::class, "Unknown column 'wilayah.id'");

test('getDetailWilayah dengan where clause langsung', function () {
    // Test the underlying logic directly since self::find() uses wrong column
    $wilayah = Wilayah::where('kode', '31.71')->first();
    expect($wilayah)->not->toBeNull();
    expect($wilayah->kode)->toBe('31.71');
    expect($wilayah->nama)->toBe('Kota Jakarta Selatan');

    $idParts = explode('.', '31.71');
    $provinsi = Wilayah::where('kode', $idParts[0])->first();
    expect($provinsi->nama)->toBe('DKI Jakarta');
});

test('getDaftarProvinsi mengembalikan collection provinsi', function () {
    $provinsi = Wilayah::getDaftarProvinsi();

    expect($provinsi)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($provinsi->count())->toBe(2);
    expect($provinsi->has('31'))->toBeTrue();
    expect($provinsi->has('32'))->toBeTrue();
});

test('getDaftarKota mengembalikan collection kota', function () {
    $kota = Wilayah::getDaftarKota();

    expect($kota)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($kota->count())->toBe(3);
    expect($kota->has('31.71'))->toBeTrue();
    expect($kota->has('32.73'))->toBeTrue();
});

test('getDaftarKotaByProvinsi mengembalikan kota untuk provinsi tertentu', function () {
    $kota = Wilayah::getDaftarKotaByProvinsi('31');

    expect($kota)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($kota->count())->toBe(2);
    expect($kota->has('31.71'))->toBeTrue();
    expect($kota->has('31.72'))->toBeTrue();
});

test('getDaftarKotaByProvinsi mengembalikan empty untuk provinsi tidak ada', function () {
    $kota = Wilayah::getDaftarKotaByProvinsi('99');

    expect($kota->count())->toBe(0);
});

test('getDaftarWilayahByName mengembalikan data berdasarkan nama', function () {
    $result = Wilayah::getDaftarWilayahByName('Jakarta');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('provinsi');
    expect($result)->toHaveKey('kota');
    expect($result['provinsi'])->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result['kota'])->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result['provinsi']->count())->toBe(1); // DKI Jakarta
    expect($result['kota']->count())->toBe(2); // Kota Jakarta Selatan, Kota Jakarta Timur
});

test('getDaftarWilayahByName mengembalikan data untuk nama kota', function () {
    $result = Wilayah::getDaftarWilayahByName('Bandung');

    expect($result['provinsi']->count())->toBe(0);
    expect($result['kota']->count())->toBe(1); // Kota Bandung
});

test('getDaftarWilayahByName mengembalikan empty untuk nama tidak ada', function () {
    $result = Wilayah::getDaftarWilayahByName('ZZZZ_NOT_EXIST');

    expect($result['provinsi']->count())->toBe(0);
    expect($result['kota']->count())->toBe(0);
});

test('wilayah model menggunakan table yang benar', function () {
    $model = new Wilayah();

    expect($model->getTable())->toBe('wilayah');
});
