<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Harga dasar Rp20.000 per 30 hari; tingkatan hemat 20%/15%/10%/5%/0% dari acuan 1 Bulan.
            $paketAwal = [
                // Label => [durasi_hari, harga].
                '1 Tahun' => [365, 192000], // hemat 20%, Rp16.000/bulan
                '9 Bulan' => [270, 153000], // hemat 15%, Rp17.000/bulan
                '6 Bulan' => [180, 108000], // hemat 10%, Rp18.000/bulan
                '3 Bulan' => [90, 57000], // hemat 5%, Rp19.000/bulan
                '1 Bulan' => [30, 20000], // harga acuan
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
