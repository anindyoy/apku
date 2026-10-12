<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Pilihan paket dari landing page yang terbawa melewati registrasi dan onboarding.
            $table->foreignId('paket_langganan_dipilih_id')
                ->nullable()
                ->constrained('paket_langganans')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['paket_langganan_dipilih_id']);
            $table->dropColumn('paket_langganan_dipilih_id');
        });
    }
};
