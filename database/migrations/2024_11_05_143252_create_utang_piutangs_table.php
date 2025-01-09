<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('utang_piutang', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('tipe', ['utang', 'piutang']);
            $table->string('kepada', 150);
            $table->string('deskripsi')->nullable();
            $table->boolean('sambung_kas')->default(false);
            $table->date('tempo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utang_piutangs');
    }
};
