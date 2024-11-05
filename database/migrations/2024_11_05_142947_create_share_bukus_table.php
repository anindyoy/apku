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
        Schema::create('share_buku', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buku_kas_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('privilege', ['editor', 'viewer']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_bukus');
    }
};
