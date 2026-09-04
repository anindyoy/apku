<?php

use App\Enums\StatusLangganan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langganans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_order')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('paket_langganan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('metode_pembayaran_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_paket');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('durasi_hari');
            $table->string('label_metode_pembayaran');
            $table->json('detail_pembayaran');
            $table->string('status')->default(StatusLangganan::MenungguPembayaran->value)->index();
            $table->string('bukti_pembayaran_path')->nullable();
            $table->timestamp('tanggal_konfirmasi')->nullable();
            $table->text('catatan_user')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->date('masa_aktif_mulai')->nullable();
            $table->date('masa_aktif_sampai')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langganans');
    }
};
