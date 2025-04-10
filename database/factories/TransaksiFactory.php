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
        $user = User::inRandomOrder()->notSuper()->first();

        return [
            'user_id' => $user->id,
            'jenis_transaksi_id' => JenisTransaksi::whereUserId($user->id)->inRandomOrder()->first()->id,
            'buku_kas_id' => fn(array $attributes) => $attributes['buku_kas_id']
                ?? BukuKas::whereUserId($attributes['user_id'])->inRandomOrder()->first()->id,
            'tanggal' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'nominal' => rand(1, 100),
            'deskripsi' => 'data seeder',
            'jenis' => $this->faker->randomElement(['Pemasukan', 'Pengeluaran']),
        ];
    }

    /**
     * Indicate that the transaction is for income.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pemasukan()
    {
        return $this->state(function (array $attributes) {
            return [
                'jenis' => 'Pemasukan',
            ];
        });
    }

    /**
     * Indicate that the transaction is for expense.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pengeluaran()
    {
        return $this->state(function (array $attributes) {
            return [
                'jenis' => 'Pengeluaran',
            ];
        });
    }

    /**
     * Set the user ID for this transaction.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forUser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            $data = [
                'user_id' => $userId,
                'jenis_transaksi_id' => JenisTransaksi::whereUserId($userId)->inRandomOrder()->first()->id,
            ];

            // Hanya tambahkan buku_kas_id jika tidak ada di attributes
            if (!isset($attributes['buku_kas_id'])) {
                $data['buku_kas_id'] = BukuKas::whereUserId($userId)->inRandomOrder()->first()->id;
            }

            return $data;
        });
    }
}