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
        // Transaksi::factory()->count(3)->make();

        $user = User::whereHas('buku_kas')
            ->inRandomOrder()->first()->id;

        Transaksi::create([
            'user_id' => $user,
            'jenis_transaksi_id' => JenisTransaksi::inRandomOrder()->first()->id,
            'buku_kas_id' => 9,
            // 'buku_kas_id' => BukuKas::getRandomBukuKas($user)->first()->id,
            'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
            'nominal' => rand(1, 100),
            // 'jenis' => rand(0, 1) ? 'Pengeluaran' : 'Pemasukan',
            'jenis' => 'Pemasukan',
            'deskripsi' => fake()->sentence(),
            // 'tujuan_buku_tabungan_id' => $tujuan_kas_id,
        ]);

        // foreach (User::all() as $key => $value) {
        //     if ($value->type == 'premium') {
        //         $value->masa_aktif = fake()->dateTimeBetween('+3 months', '+1 year');
        //         $value->save();
        //     }
        // }

        // foreach (BukuKas::all() as $key => $value) {
        //     $value->nama_buku = fake()->word();
        //     $value->save();
        // }
    }
}
