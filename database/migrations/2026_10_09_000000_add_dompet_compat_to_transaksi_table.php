<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
        // Kolom aktual tetap sumber_dana_id; dompet_id disinkronkan via trigger.
        if (! Schema::hasColumn('transaksi', 'dompet_id')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->unsignedBigInteger('dompet_id')->nullable()->after('sumber_dana_id');
                $table->index('dompet_id');
            });

            DB::table('transaksi')->whereNull('dompet_id')->update([
                'dompet_id' => DB::raw('sumber_dana_id'),
            ]);
        }

        // Sinkron dua arah agar query lama (where dompet_id) dan kode baru tetap konsisten.
        DB::unprepared('DROP TRIGGER IF EXISTS trg_transaksi_sync_dompet_bi');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_transaksi_sync_dompet_bi
            BEFORE INSERT ON transaksi
            FOR EACH ROW
            BEGIN
                IF NEW.sumber_dana_id IS NULL AND NEW.dompet_id IS NOT NULL THEN
                    SET NEW.sumber_dana_id = NEW.dompet_id;
                ELSEIF NEW.dompet_id IS NULL AND NEW.sumber_dana_id IS NOT NULL THEN
                    SET NEW.dompet_id = NEW.sumber_dana_id;
                ELSEIF NEW.sumber_dana_id IS NOT NULL AND NEW.dompet_id IS NOT NULL AND NEW.dompet_id <> NEW.sumber_dana_id THEN
                    SET NEW.dompet_id = NEW.sumber_dana_id;
                END IF;
            END
            SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_transaksi_sync_dompet_bu');
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_transaksi_sync_dompet_bu
            BEFORE UPDATE ON transaksi
            FOR EACH ROW
            BEGIN
                IF NEW.sumber_dana_id <> OLD.sumber_dana_id OR (NEW.sumber_dana_id IS NULL <> OLD.sumber_dana_id IS NULL) THEN
                    SET NEW.dompet_id = NEW.sumber_dana_id;
                ELSEIF NEW.dompet_id <> OLD.dompet_id OR (NEW.dompet_id IS NULL <> OLD.dompet_id IS NULL) THEN
                    SET NEW.sumber_dana_id = NEW.dompet_id;
                END IF;
            END
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_transaksi_sync_dompet_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_transaksi_sync_dompet_bi');

        if (Schema::hasColumn('transaksi', 'dompet_id')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropIndex(['dompet_id']);
                $table->dropColumn('dompet_id');
            });
        }
    }
};
