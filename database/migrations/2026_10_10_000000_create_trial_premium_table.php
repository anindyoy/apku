<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_premium', function (Blueprint $table) {
            $table->id();
            // Catatan dipertahankan saat akun dihapus agar trial tidak bisa diulang.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('hp_normal', 30)->unique();
            $table->string('email_normal', 255)->unique();
            $table->date('mulai_pada');
            $table->date('berakhir_pada');
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('langganan_id')->nullable()->constrained('langganans')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('dikonversi_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'berakhir_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_premium');
    }
};
