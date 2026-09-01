<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->unique(['user_id', 'nama_jenis', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'nama_jenis', 'tipe']);
        });
    }
};
