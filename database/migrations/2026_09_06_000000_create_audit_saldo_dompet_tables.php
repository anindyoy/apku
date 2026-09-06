<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('tipe');
        });

        Schema::create('audit_saldo_dompet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('buku_kas_id')->constrained('buku_kas')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('tanggal');
            $table->text('catatan');
            $table->bigInteger('total_saldo_aplikasi');
            $table->bigInteger('total_saldo_riil');
            $table->bigInteger('total_selisih');
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
        });

        Schema::create('audit_saldo_dompet_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_saldo_dompet_id')->constrained('audit_saldo_dompet')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('dompet_id')->constrained('dompet')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nama_dompet', 50);
            $table->bigInteger('saldo_aplikasi');
            $table->bigInteger('saldo_riil');
            $table->bigInteger('selisih');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['audit_saldo_dompet_id', 'dompet_id']);
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('audit_saldo_dompet_detail_id')
                ->nullable()
                ->after('import_transaksi_id')
                ->constrained('audit_saldo_dompet_detail')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audit_saldo_dompet_detail_id');
        });

        Schema::dropIfExists('audit_saldo_dompet_detail');
        Schema::dropIfExists('audit_saldo_dompet');

        Schema::table('jenis_transaksi', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
