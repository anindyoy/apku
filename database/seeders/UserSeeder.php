<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Mengisi data pengguna contoh.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $userIds = User::notAdmin()->pluck('id');
            $kasIds = DB::table('buku_kas')->whereIn('user_id', $userIds)->pluck('id');
            $tabunganIds = DB::table('tabungan_emas')->whereIn('buku_kas_id', $kasIds)->pluck('id');

            DB::table('transaksi_emas')
                ->whereIn('tabungan_emas_id', $tabunganIds)
                ->orWhereIn('user_id', $userIds)
                ->delete();
            DB::table('tabungan_emas')->whereIn('id', $tabunganIds)->delete();
            User::notAdmin()->delete();
        });

        User::factory()->create([
            'name' => 'Pengguna Reguler',
            'email' => 'reguler@apku.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'type' => 'reguler',
            'masa_aktif' => null,
        ]);

        User::factory()->create([
            'name' => 'Pengguna Premium',
            'email' => 'premium@apku.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'type' => 'premium',
            'masa_aktif' => now()->addMonths(6),
        ]);

        User::factory(8)->create(['role' => 'user']);
    }
}
