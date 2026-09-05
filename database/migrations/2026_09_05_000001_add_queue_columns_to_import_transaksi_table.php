<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_transaksi', function (Blueprint $table): void {
            $table->string('path_file')->nullable()->after('hash_file');
            $table->json('pemetaan')->nullable()->after('path_file');
            $table->boolean('buat_kategori_otomatis')->default(false)->after('pemetaan');
            $table->unsignedInteger('jumlah_diproses')->default(0)->after('jumlah_baris');
            $table->text('pesan_error')->nullable()->after('status');
            $table->timestamp('mulai_diproses_at')->nullable()->after('pesan_error');
            $table->timestamp('selesai_diproses_at')->nullable()->after('mulai_diproses_at');
        });
    }

    public function down(): void
    {
        Schema::table('import_transaksi', function (Blueprint $table): void {
            $table->dropColumn([
                'path_file',
                'pemetaan',
                'buat_kategori_otomatis',
                'jumlah_diproses',
                'pesan_error',
                'mulai_diproses_at',
                'selesai_diproses_at',
            ]);
        });
    }
};
