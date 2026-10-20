<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menambahkan kolom is_tabungan pada buku_kas dan hitung_tabungan_di_total pada users.
    public function up(): void
    {
        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->boolean('is_tabungan')->default(false)->after('is_default');
            $table->index(['user_id', 'is_tabungan', 'nama_buku']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('hitung_tabungan_di_total')->default(true)->after('tabungan_emas_aktif');
        });
    }

    // Menghapus kolom dan indeks kas tabungan yang ditambahkan.
    public function down(): void
    {
        Schema::table('buku_kas', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'is_tabungan', 'nama_buku']);
            $table->dropColumn('is_tabungan');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('hitung_tabungan_di_total');
        });
    }
};
