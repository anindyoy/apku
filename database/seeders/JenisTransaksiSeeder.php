<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\JenisTransaksi;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JenisTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
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
        }
    }
}
