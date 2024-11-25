<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BukuKas;
use App\Models\UtangPiutang;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
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
        // User::truncate();

        // User::factory()->create([
        //     'name' => 'Super User',
        //     'email' => 'super@apku.com',
        //     'password' => Hash::make('superapku'),
        //     'role' => 'super',
        // ]);

        // User::factory(10)->create();


        // JenisTransaksi::truncate();
        // $this->call([JenisTransaksiSeeder::class]);

        $this->call([TransaksiSeeder::class]);

        $this->call([WilayahSeeder::class]);

        $this->call([UtangPiutangSeeder::class]);
    }
}
