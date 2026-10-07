<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_saldo_dompet', function (Blueprint $table) {
            $table->text('catatan')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('audit_saldo_dompet')->whereNull('catatan')->update(['catatan' => '']);

        Schema::table('audit_saldo_dompet', function (Blueprint $table) {
            $table->text('catatan')->nullable(false)->change();
        });
    }
};
