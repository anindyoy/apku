<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Builder;
use Database\Seeders\JenisTransaksiSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BukuKas::truncate();
        Transaksi::truncate();

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
            // CREATE BUKU KAS & TRANSAKSI PERTAMA
            for ($i = 0; $i < rand(2, 4); $i++) {
                $buku = BukuKas::factory()->create([
                    'user_id' => $value->id,
                    'nama_buku' => $i == 0 ? 'Kas Utama' : fake()->word(),
                ]);

                $transaksi = Transaksi::factory()->create([
                    'user_id' => $value->id,
                    'buku_kas_id' => $buku->id,
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo pertama',
                ]);

                $buku->saldo += $transaksi->nominal;
                $buku->save();
            }

            // CREATE TRANSAKSI
            for ($i = 0; $i < rand(10, 25); $i++) {
                $kas = BukuKas::getRandomBukuKas($value->id)->first();
                $jenis = rand(0, 3) != 3 ? 'Pengeluaran' : 'Pemasukan';
                $jenis_transaksi_id = JenisTransaksi::whereUserId($value->id)
                    ->whereTipe($jenis)
                    ->inRandomOrder()
                    ->first()
                    ->id;

                $transaksi = Transaksi::factory()->create([
                    'user_id' => $value->id,
                    'buku_kas_id' => $kas->id,
                    'tanggal' => fake()->dateTimeBetween(
                        $kas->transaksi()->first()->tanggal,
                        'now'
                    ),
                    'jenis_transaksi_id' => $jenis_transaksi_id,
                    'jenis' => $jenis,
                    'deskripsi' => fake()->optional()->words(rand(2, 5), true)
                ]);

                if ($transaksi->jenis == 'Pengeluaran') {
                    $kas->saldo -= $transaksi->nominal;
                    $kas->save();
                } else {
                    $kas->saldo += $transaksi->nominal;
                    $kas->save();
                }
            }
        }
    }
}
