<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Menyisipkan nomor WhatsApp admin default jika belum ada data kontak.
        DB::table('application_settings')->upsert(
            [
                'key' => 'kontak',
                'value' => json_encode(['wa' => '628567851359']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            uniqueBy: ['key'],
            update: [],
        );
    }

    public function down(): void
    {
        DB::table('application_settings')->where('key', 'kontak')->delete();
    }
};
