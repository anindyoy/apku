<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menambahkan preferensi penghitung uang kas pada pengguna.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('tampilkan_hitung_uang_kas')->default(false)->after('hitung_tabungan_di_total');
        });
    }

    // Menghapus preferensi penghitung uang kas.
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('tampilkan_hitung_uang_kas');
        });
    }
};
