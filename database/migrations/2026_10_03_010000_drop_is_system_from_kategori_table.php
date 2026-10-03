<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transaksi audit saldo dikenali lewat audit_saldo_dompet_detail_id, bukan kategori sistem.
        // Foreign key transaksi mengosongkan kategori_id saat kategori sistem dihapus.
        DB::table('kategori')->where('is_system', true)->delete();

        Schema::table('kategori', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('tipe');
        });
    }
};
