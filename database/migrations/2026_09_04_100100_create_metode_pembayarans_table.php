<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metode_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('jenis', 50);
            $table->string('nama_penyedia');
            $table->string('nomor_tujuan')->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->text('instruksi')->nullable();
            $table->string('gambar_qr_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metode_pembayarans');
    }
};
