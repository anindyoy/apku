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
            $table->foreignId('buku_kas_id')
                ->constrained('buku_kas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('jenis_transaksi_id')
                ->nullable()
                ->constrained('jenis_transaksi')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->dateTime('tanggal');
            $table->integer('nominal');
            $table->enum('jenis', ['Pengeluaran', 'Pemasukan', 'Transfer Pemasukan', 'Transfer Pengeluaran']);
            $table->string('transfer_code', 30)->nullable();
            $table->text('deskripsi')->nullable();
            $table->foreignId('tujuan_buku_tabungan_id')
                ->nullable()
                ->constrained('buku_kas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('asal_buku_tabungan_id')
                ->nullable()
                ->constrained('buku_kas')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
