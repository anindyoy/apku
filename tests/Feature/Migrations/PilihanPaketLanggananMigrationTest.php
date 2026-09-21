<?php

use Illuminate\Support\Facades\DB;

test('migration pilihan paket membuat empat paket nonaktif tanpa harga dan tidak menggandakan data', function () {
    $labels = ['1 Tahun' => 365, '9 Bulan' => 270, '6 Bulan' => 180, '3 Bulan' => 90];
    DB::table('paket_langganans')->whereIn('label', array_keys($labels))->delete();
    $migration = require database_path('migrations/2026_09_21_000000_insert_pilihan_paket_langganan.php');

    $migration->up();
    $migration->up();

    $paket = DB::table('paket_langganans')->whereIn('label', array_keys($labels))->get()->keyBy('label');
    expect($paket)->toHaveCount(4);
    foreach ($labels as $label => $durasi) {
        expect((int) $paket[$label]->durasi_hari)->toBe($durasi)
            ->and((int) $paket[$label]->harga)->toBe(0)
            ->and((bool) $paket[$label]->is_active)->toBeFalse();
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
