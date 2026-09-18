<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_transaksi', function (Blueprint $table): void {
            $table->boolean('pengaruhi_saldo')->default(true);
        });
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->boolean('pengaruhi_saldo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', fn (Blueprint $table) => $table->dropColumn('pengaruhi_saldo'));
        Schema::table('import_transaksi', fn (Blueprint $table) => $table->dropColumn('pengaruhi_saldo'));
    }
};
