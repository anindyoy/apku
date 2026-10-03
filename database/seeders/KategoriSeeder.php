<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Schema;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Kategori::truncate();
        });

        // Buat kategori awal untuk setiap pengguna
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
                    Kategori::create([
                        'user_id' => $value->id,
                        'tipe' => $tipe,
                        'nama' => $value3
                    ]);
                }
            }
        }
    }
}
