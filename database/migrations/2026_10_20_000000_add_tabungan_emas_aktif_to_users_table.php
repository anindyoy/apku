<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menambahkan kolom pengaturan aktivasi fitur tabungan emas per user.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('tabungan_emas_aktif')->default(false);
        });
    }

    // Menghapus kolom pengaturan aktivasi fitur tabungan emas per user.
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('tabungan_emas_aktif');
        });
    }
};
