<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Harga dasar Rp20.000 per 30 hari; tingkatan hemat 0%/5%/10%/15%/20% dari acuan 1 Bulan.
            $paketAwal = [
                // Label => [durasi_hari, harga].
                '1 Tahun' => [365, 180000], // diskon 25%, ~15.000/bulan
                '9 Bulan' => [270, 150000], // diskon ~16,7%, ~16.667/bulan
                '6 Bulan' => [180, 105000], // diskon 12,5%, 17.500/bulan
                '3 Bulan' => [90, 55000],   // diskon ~8,3%, ~18.333/bulan
                '1 Bulan' => [30, 20000],   // harga normal
            ];

            foreach ($paketAwal as $label => [$durasi, $harga]) {
                if (DB::table('paket_langganans')->where('label', $label)->exists()) {
                    continue;
                }

                DB::table('paket_langganans')->insert([
                    'label' => $label,
                    'harga' => $harga,
                    'durasi_hari' => $durasi,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Pertahankan data paket karena mungkin sudah diubah admin atau digunakan pada order.
    }
};
