<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['1 Tahun' => 365, '9 Bulan' => 270, '6 Bulan' => 180, '3 Bulan' => 90] as $label => $durasi) {
                if (DB::table('paket_langganans')->where('label', $label)->exists()) {
                    continue;
                }

                DB::table('paket_langganans')->insert([
                    'label' => $label,
                    'harga' => 0,
                    'durasi_hari' => $durasi,
                    'is_active' => false,
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
