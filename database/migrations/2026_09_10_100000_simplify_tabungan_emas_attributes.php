<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->renameColumn('nama', 'label');
            $table->dropColumn(['merek', 'produk']);
        });
    }

    public function down(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->renameColumn('label', 'nama');
            $table->string('merek', 100)->nullable();
            $table->string('produk', 150)->nullable();
        });
    }
};
