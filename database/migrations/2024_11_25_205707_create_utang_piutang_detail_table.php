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
        Schema::create('utang_piutang_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('utang_piutang_id');
            $table->integer('nominal');
            $table->enum('tipe', ['tambah', 'kurang']);
            $table->string('deskripsi')->nullable();
            $table->dateTime('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
