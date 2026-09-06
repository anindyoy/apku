<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dompet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama_dompet', 50);
            $table->bigInteger('saldo')->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('description', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'nama_dompet']);
            $table->index(['user_id', 'is_default']);
        });

        Schema::table('buku_kas', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('saldo');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('transfer_code', 36)->nullable()->change();
            $table->foreignId('dompet_id')
                ->nullable()
                ->after('buku_kas_id')
                ->constrained('dompet')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('tipe_transfer', 20)->nullable()->after('transfer_code');
        });

        $waktu = now();

        DB::table('users')->orderBy('id')->each(function (object $user) use ($waktu): void {
            $bukuKasUtama = DB::table('buku_kas')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->first();

            if (! $bukuKasUtama) {
                $bukuKasId = DB::table('buku_kas')->insertGetId([
                    'user_id' => $user->id,
                    'nama_buku' => 'Kas Utama',
                    'saldo' => 0,
                    'is_default' => true,
                    'description' => 'Kas utama',
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            } else {
                $bukuKasId = $bukuKasUtama->id;
                DB::table('buku_kas')->where('id', $bukuKasId)->update(['is_default' => true]);
            }

            $saldo = (int) DB::table('buku_kas')->where('user_id', $user->id)->sum('saldo');
            $dompetId = DB::table('dompet')->insertGetId([
                'user_id' => $user->id,
                'nama_dompet' => 'Cash',
                'saldo' => $saldo,
                'is_default' => true,
                'description' => 'Dompet tunai utama',
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);

            DB::table('transaksi')->where('user_id', $user->id)->update(['dompet_id' => $dompetId]);
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('dompet_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dompet_id');
            $table->dropColumn('tipe_transfer');
            $table->string('transfer_code', 30)->nullable()->change();
        });

        Schema::table('buku_kas', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });

        Schema::dropIfExists('dompet');
    }
};
