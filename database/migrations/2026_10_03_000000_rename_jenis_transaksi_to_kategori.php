<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['jenis_transaksi_id']);
        });

        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'nama_jenis', 'tipe']);
            $table->renameColumn('nama_jenis', 'nama');
        });

        Schema::rename('jenis_transaksi', 'kategori');

        Schema::table('kategori', function (Blueprint $table) {
            $table->unique(['user_id', 'nama', 'tipe']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->renameColumn('jenis_transaksi_id', 'kategori_id');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreign('kategori_id')->references('id')->on('kategori')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->renameColumn('kategori_id', 'jenis_transaksi_id');
        });

        Schema::table('kategori', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'nama', 'tipe']);
            $table->renameColumn('nama', 'nama_jenis');
        });

        Schema::rename('kategori', 'jenis_transaksi');

        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->unique(['user_id', 'nama_jenis', 'tipe']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreign('jenis_transaksi_id')->references('id')->on('jenis_transaksi')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
