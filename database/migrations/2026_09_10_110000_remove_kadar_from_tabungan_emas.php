<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->dropColumn('kadar');
        });
    }

    public function down(): void
    {
        Schema::table('tabungan_emas', function (Blueprint $table) {
            $table->decimal('kadar', 5, 2)->default(99.99);
        });
    }
};
