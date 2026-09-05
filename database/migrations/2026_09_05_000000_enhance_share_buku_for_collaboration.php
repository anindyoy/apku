<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_buku', function (Blueprint $table): void {
            $table->dateTime('berlaku_mulai')->nullable()->after('privilege');
            $table->dateTime('berlaku_sampai')->nullable()->after('berlaku_mulai');
            $table->foreignId('invited_by_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->unique(['buku_kas_id', 'user_id']);
            $table->index(['user_id', 'berlaku_mulai', 'berlaku_sampai'], 'share_buku_akses_index');
        });

        DB::table('share_buku')->whereNull('berlaku_mulai')->update(['berlaku_mulai' => now()]);
    }

    public function down(): void
    {
        Schema::table('share_buku', function (Blueprint $table): void {
            $table->dropIndex('share_buku_akses_index');
            $table->dropUnique(['buku_kas_id', 'user_id']);
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropColumn(['berlaku_mulai', 'berlaku_sampai']);
        });
    }
};
