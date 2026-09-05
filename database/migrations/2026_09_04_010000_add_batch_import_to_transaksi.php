<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_transaksi', function (Blueprint $table): void {
            $table->timestamp('dibatalkan_at')->nullable()->after('status');
        });

        Schema::table('transaksi', function (Blueprint $table): void {
            $table->foreignId('import_transaksi_id')
                ->nullable()
                ->after('user_id')
                ->constrained('import_transaksi')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('import_transaksi_id');
        });

        Schema::table('import_transaksi', function (Blueprint $table): void {
            $table->dropColumn('dibatalkan_at');
        });
    }
};
