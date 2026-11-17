<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_default');
        });

        Schema::table('sumber_dana', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('sumber_dana', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
