<?php

use Illuminate\Support\Facades\DB;

test('migration pilihan paket membuat lima paket aktif dengan tingkatan hemat dan tidak menggandakan data', function () {
    // Harga dasar Rp20.000 per 30 hari; tingkatan hemat 20%/15%/10%/5%/0% dari acuan 1 Bulan.
    $labels = ['1 Tahun' => [365, 192000], '9 Bulan' => [270, 153000], '6 Bulan' => [180, 108000], '3 Bulan' => [90, 57000], '1 Bulan' => [30, 20000]];
    DB::table('paket_langganans')->whereIn('label', array_keys($labels))->delete();
    $migration = require database_path('migrations/2026_09_21_000000_insert_pilihan_paket_langganan.php');

    $migration->up();
    $migration->up();

    $paket = DB::table('paket_langganans')->whereIn('label', array_keys($labels))->get()->keyBy('label');
    expect($paket)->toHaveCount(5);
    foreach ($labels as $label => [$durasi, $harga]) {
        expect((int) $paket[$label]->durasi_hari)->toBe($durasi)
            ->and((int) $paket[$label]->harga)->toBe($harga)
            ->and((bool) $paket[$label]->is_active)->toBeTrue();
    }
});

test('migration pilihan paket mempertahankan paket yang telah diatur admin termasuk saat rollback', function () {
    $migration = require database_path('migrations/2026_09_21_000000_insert_pilihan_paket_langganan.php');
    $migration->up();
    DB::table('paket_langganans')->where('label', '1 Tahun')->update(['harga' => 150000, 'is_active' => true]);
    $sebelum = DB::table('paket_langganans')->orderBy('id')->get();

    $migration->up();
    $migration->down();

    expect(DB::table('paket_langganans')->orderBy('id')->get()->toArray())->toEqual($sebelum->toArray());
});
