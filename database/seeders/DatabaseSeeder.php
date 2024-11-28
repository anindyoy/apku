<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BukuKas;
use App\Models\UtangPiutang;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\TransaksiSeeder;
use Database\Seeders\UtangPiutangSeeder;
use Database\Seeders\JenisTransaksiSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            JenisTransaksiSeeder::class,
            TransaksiSeeder::class,
            WilayahSeeder::class,
            UtangPiutangSeeder::class,
        ]);
    }
}
