<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            DB::table('kategori_kas')->truncate();
            Kategori::truncate();
        });

        // Buat kategori awal untuk setiap pengguna
        $daftarTipe = ['Pemasukan', 'Pengeluaran', 'Semua'];
        $namaKategori = [
            'Transfer',
            'Usaha',
            'Investasi',
            'Rumah Tangga',
            'Pendidikan',
            'Hiburan',
            'Gaji',
            'Bonus',
            'Hadiah',
            'Transportasi',
            'Kesehatan',
            'Lainnya',
        ];

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $user) {
            foreach ($daftarTipe as $tipe) {
                $namaTerpilih = fake()->randomElements($namaKategori, rand(3, 5));
                foreach ($namaTerpilih as $nama) {
                    Kategori::create([
                        'user_id' => $user->id,
                        'dibuat_oleh' => $user->id,
                        'tipe' => $tipe,
                        'nama' => $nama,
                    ]);
                }
            }
        }
    }
}
