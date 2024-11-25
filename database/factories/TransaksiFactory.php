<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaksi>
 */
class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // $user = User::inRandomOrder()->notSuper()->first();
        return [
            // 'user_id' => $user->id,
            // 'jenis_transaksi_id' => JenisTransaksi::whereUserId($user->id)->inRandomOrder()->first()->id,
            // 'buku_kas_id' => BukuKas::getRandomBukuKas($user->id)->first()->id,
            'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
            'nominal' => rand(1, 100),
            'jenis' => rand(0, 1) ? 'Pengeluaran' : 'Pemasukan',
            'deskripsi' => fake()->sentence(),
        ];
    }
}
