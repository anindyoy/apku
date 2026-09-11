<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi basis data aplikasi.
     */
    public function run(): void
    {
        app(DemoFiturSeeder::class)->bersihkanDataDemo();

        $this->call([
            UserSeeder::class,
            JenisTransaksiSeeder::class,
            TransaksiSeeder::class,
            UtangPiutangSeeder::class,
            DemoFiturSeeder::class,
        ]);
    }
}
