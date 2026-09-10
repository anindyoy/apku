<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->unsignedBigInteger('harga_beli')->nullable();
            $table->text('keterangan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->dropColumn(['harga_beli', 'keterangan']);
        });
    }
};
