<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE kategori MODIFY tipe ENUM('Pemasukan','Pengeluaran','Semua') NOT NULL");
    }

    public function down(): void
    {
        // Belum ada data production: kategori bertipe Semua dialihkan ke Pengeluaran agar enum dapat dipersempit kembali.
        DB::table('kategori')->where('tipe', 'Semua')->update(['tipe' => 'Pengeluaran']);
        DB::statement("ALTER TABLE kategori MODIFY tipe ENUM('Pemasukan','Pengeluaran') NOT NULL");
    }
};
