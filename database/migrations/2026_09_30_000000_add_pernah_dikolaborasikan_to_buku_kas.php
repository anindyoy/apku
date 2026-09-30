<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->boolean('pernah_dikolaborasikan')->default(false);
        });

        DB::table('buku_kas')->where(function ($query): void {
            $query->whereExists(function ($query): void {
                $query->selectRaw('1')->from('share_buku')
                    ->whereColumn('share_buku.buku_kas_id', 'buku_kas.id')
                    ->whereColumn('share_buku.user_id', '<>', 'buku_kas.user_id');
            })->orWhereExists(function ($query): void {
                $query->selectRaw('1')->from('transaksi')
                    ->whereColumn('transaksi.buku_kas_id', 'buku_kas.id')
                    ->whereColumn('transaksi.user_id', '<>', 'buku_kas.user_id');
            });
        })->update(['pernah_dikolaborasikan' => true]);
    }

    public function down(): void
    {
        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->dropColumn('pernah_dikolaborasikan');
        });
    }
};
