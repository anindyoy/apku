<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JenisTransaksi::truncate();
        BukuKas::truncate();
        ShareBuku::truncate();
        Transaksi::truncate();

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
            if ($value->type == 'premium') {
                $value->masa_aktif = fake()->dateTimeBetween('+3 months', '+1 year');
                $value->save();
            }

            // CREATE JENIS TRANSAKSI
            $list_tipe = ['Pemasukan', 'Pengeluaran'];
            $jenis = [
                'transfer',
                'usaha',
                'investasi',
                'rumah_tangga',
                'pendidikan',
                'hiburan',
                'gaji',
                'bonus',
                'hadiah',
                'transportasi',
                'kesehatan',
                'lainnya'
            ];

            foreach ($list_tipe as $key => $tipe) {
                $jenisRandom = fake()->randomElements($jenis, rand(3, 5));

                foreach ($jenisRandom as $value3) {
                    JenisTransaksi::create([
                        'user_id' => $value->id,
                        'tipe' => $tipe,
                        'nama_jenis' => $value3
                    ]);
                }
            }

            // CREATE BUKU KAS & SHARE BUKU
            for ($i = 0; $i < rand(2, 4); $i++) {
                $goal = rand(0, 4);
                $buku = BukuKas::create([
                    'user_id' => $value->id,
                    'nama_buku' => 'Buku Kas-' . ($i + 1) . ' ' . fake()->word(),
                    'saldo' => 0,
                    'description' => fake()->sentence(),
                    'goal' => $goal == 4 ? rand(100, 1000) : null,
                    'tanggal_goal' => $goal == 4 ? fake()->dateTimeBetween('now', '+1 year') : null,
                ]);

                Transaksi::create([
                    'user_id' => $value->id,
                    'buku_kas_id' => $buku->id,
                    'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
                    'nominal' => rand(1, 100) . '000',
                    'jenis' => 'Pemasukan',
                    'deskripsi' => 'Saldo pertama',
                    // 'tujuan_buku_tabungan_id' => $tujuan_kas_id,
                ]);

                // if (rand(0, 1)) {
                //     ShareBuku::create([
                //         'user_id' => User::inRandomOrder()->whereNot('id', $value->id)->first()->id,
                //         'buku_kas_id' => $buku->id,
                //         'privilege' => rand(0, 1) ? 'editor' : 'viewer',
                //     ]);
                // }
            }

            // CREATE TRANSAKSI
            for ($i = 0; $i < rand(10, 25); $i++) {
                $default_kas_id = BukuKas::getRandomBukuKas($value->id)->first()->id;

                // $tujuan_kas_id = !rand(0, 3)
                //     ? BukuKas::getRandomBukuKas($value->id)->whereNot('id', $default_kas_id)->first()->id
                //     : null;

                $kas_id = BukuKas::getRandomBukuKas($value->id)->first()->id;

                Transaksi::create([
                    'user_id' => $value->id,
                    'jenis_transaksi_id' => JenisTransaksi::where('user_id', $value->id)->inRandomOrder()->first()->id,
                    'buku_kas_id' => $kas_id,
                    'tanggal' => fake()->dateTimeBetween(Transaksi::where('buku_kas_id', $kas_id)->first()->tanggal, 'now'),
                    'nominal' => rand(1, 100) . '000',
                    'jenis' => rand(0, 1) ? 'Pengeluaran' : 'Pemasukan',
                    'deskripsi' => fake()->sentence(),
                    // 'tujuan_buku_tabungan_id' => $tujuan_kas_id,
                ]);
            }
        }
    }
}
