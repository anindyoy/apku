<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabungan_emas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_kas_id')->constrained('buku_kas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nama', 100);
            $table->string('merek', 100)->nullable();
            $table->string('produk', 150)->nullable();
            $table->decimal('kadar', 5, 2)->default(99.99);
            $table->decimal('berat_gram', 12, 4)->default(0);
            $table->unsignedBigInteger('total_modal')->default(0);
            $table->timestamps();
        });

        Schema::create('transaksi_emas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tabungan_emas_id')->constrained('tabungan_emas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->cascadeOnUpdate()->nullOnDelete();
            $table->string('jenis', 20);
            $table->dateTime('tanggal');
            $table->decimal('berat_gram', 12, 4);
            $table->unsignedBigInteger('harga_per_gram')->nullable();
            $table->unsignedBigInteger('biaya_tambahan')->default(0);
            $table->unsignedBigInteger('total_rupiah')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('harga_emas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_kas_id')->nullable()->constrained('buku_kas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('provider', 100);
            $table->string('sumber', 20);
            $table->string('jenis_harga', 20)->default('buyback');
            $table->unsignedBigInteger('harga_per_gram');
            $table->dateTime('berlaku_pada');
            $table->dateTime('diambil_pada');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['sumber', 'jenis_harga', 'berlaku_pada']);
            $table->index(['buku_kas_id', 'sumber', 'berlaku_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_emas');
        Schema::dropIfExists('transaksi_emas');
        Schema::dropIfExists('tabungan_emas');
    }
};
