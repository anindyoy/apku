<?php

use App\Models\Voucher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Menyelaraskan kode voucher untuk semua voucher berulang yang sudah ada di database.
    public function up(): void
    {
        $vouchers = Voucher::query()->where('dapat_dipakai_berulang', true)->get();
        foreach ($vouchers as $voucher) {
            $voucher->sinkronkanKodeBerulang();
        }
    }

    public function down(): void
    {
        // Tidak perlu membalikkan data kode voucher yang sudah terbuat.
    }
};
