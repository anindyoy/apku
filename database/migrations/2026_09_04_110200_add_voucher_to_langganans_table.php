<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('langganans', function (Blueprint $table) {
            $table->foreignId('voucher_code_id')->nullable()->after('metode_pembayaran_id')->constrained()->nullOnDelete();
            $table->string('kode_voucher')->nullable()->after('detail_pembayaran');
            $table->unsignedTinyInteger('persentase_diskon')->default(0)->after('kode_voucher');
            $table->unsignedBigInteger('nominal_diskon')->default(0)->after('persentase_diskon');
            $table->unsignedBigInteger('total_pembayaran')->default(0)->after('nominal_diskon');
        });

        DB::table('langganans')->update(['total_pembayaran' => DB::raw('harga')]);
    }

    public function down(): void
    {
        Schema::table('langganans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_code_id');
            $table->dropColumn(['kode_voucher', 'persentase_diskon', 'nominal_diskon', 'total_pembayaran']);
        });
    }
};
