<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_transaksi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('hash_file', 64);
            $table->unsignedInteger('jumlah_baris');
            $table->string('status', 20)->default('berhasil');
            $table->timestamps();

            $table->unique(['user_id', 'hash_file']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_transaksi');
    }
};
