<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sumber_dana', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'nama_dompet']);
            $table->string('nama_dompet_aktif', 50)
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN nama_dompet ELSE NULL END');
            $table->unique(['user_id', 'nama_dompet_aktif'], 'sumber_dana_user_nama_aktif_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sumber_dana', function (Blueprint $table) {
            $table->dropUnique('sumber_dana_user_nama_aktif_unique');
            $table->dropColumn('nama_dompet_aktif');
            $table->unique(['user_id', 'nama_dompet']);
        });
    }
};
