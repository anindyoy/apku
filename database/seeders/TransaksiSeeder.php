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

                $transaksi = Transaksi::factory()->make([
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

                $transaksi = Transaksi::factory()->create([
                    'user_id' => $value->id,
                    'buku_kas_id' => $kas->id,
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo pertama',
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
