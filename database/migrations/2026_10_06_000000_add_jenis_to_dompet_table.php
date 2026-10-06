<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dompet', function (Blueprint $table) {
            $table->string('jenis', 20)->default('tunai')->after('nama_dompet')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dompet', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
