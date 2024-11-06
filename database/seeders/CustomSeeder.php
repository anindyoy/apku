<?php

namespace Database\Seeders;

use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // foreach (User::all() as $key => $value) {
        //     if ($value->type == 'premium') {
        //         $value->masa_aktif = fake()->dateTimeBetween('+3 months', '+1 year');
        //         $value->save();
        //     }
        // }

        foreach (BukuKas::all() as $key => $value) {
            $value->nama_buku = fake()->word();
            $value->save();
        }
    }
}
