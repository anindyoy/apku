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
            'tanggal' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'buku_kas_id' => BukuKas::whereUserId($user->id)->inRandomOrder()->first()->id,
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
                'jenis_transaksi_id' => function (array $attributes) use ($userId) {
                    $jenisTransaksi = JenisTransaksi::where('user_id', $userId)
                        ->where('tipe', $attributes['jenis'])
                        ->inRandomOrder()
                        ->first();

                    return $jenisTransaksi->id;
                },
                'buku_kas_id' => BukuKas::whereUserId($userId)->inRandomOrder()->first()->id,
            ];

            return $data;
        });
    }
}
