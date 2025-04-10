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
        JenisTransaksi::truncate();

        // CREATE JENIS TRANSAKSI
        $list_tipe = ['Pemasukan', 'Pengeluaran'];

        // Jenis transaksi yang lebih realistis untuk setiap tipe
        $jenis = [
            'Pemasukan' => [
                'Gaji',
                'Bonus',
                'Hadiah',
                'Investasi',
                'Penjualan',
                'Pendapatan Usaha',
                'Lainnya'
            ],
            'Pengeluaran' => [
                'Belanja Rumah Tangga',
                'Transportasi',
                'Pendidikan',
                'Kesehatan',
                'Hiburan',
                'Tagihan Listrik',
                'Tagihan Air',
                'Cicilan',
                'Donasi',
                'Lainnya'
            ]
        ];

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
            foreach ($list_tipe as $tipe) {
                $jenisRandom = fake()->randomElements($jenis[$tipe], rand(3, 5));
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