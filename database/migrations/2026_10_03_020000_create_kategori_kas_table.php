<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::create('kategori_kas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('buku_kas_id')->constrained('buku_kas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unique(['kategori_id', 'buku_kas_id']);
        });

        DB::table('kategori')->update(['dibuat_oleh' => DB::raw('user_id')]);

        // Kategori lama berlaku untuk seluruh kas pemiliknya, jadi dihubungkan ke semua kas tersebut.
        DB::statement(
            'INSERT INTO kategori_kas (kategori_id, buku_kas_id) '
            .'SELECT kategori.id, buku_kas.id FROM kategori '
            .'INNER JOIN buku_kas ON buku_kas.user_id = kategori.user_id'
        );

        // Transaksi di kas bersama yang memakai kategori milik pencatat tidak lagi sah; kategorinya dikosongkan.
        DB::table('transaksi')
            ->whereNotNull('kategori_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('kategori_kas')
                    ->whereColumn('kategori_kas.kategori_id', 'transaksi.kategori_id')
                    ->whereColumn('kategori_kas.buku_kas_id', 'transaksi.buku_kas_id');
            })
            ->update(['kategori_id' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_kas');

        Schema::table('kategori', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });
    }
};
