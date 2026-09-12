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
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('public_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        DB::table('share_buku')->whereNull('user_id')->delete();

        Schema::table('share_buku', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
