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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buku_kas_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jenis_transaksi_id')->nullable();
            $table->dateTime('tanggal');
            $table->integer('nominal');
            // $table->integer('saldo_akhir')->nullable();
            $table->enum('jenis', ['Pengeluaran', 'Pemasukan']);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('tujuan_buku_tabungan_id')->nullable();
            // $table->enum('jenis_transaksi', ['transfer', 'pendapatan', 'pengeluaran', 'utang', 'piutang']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
