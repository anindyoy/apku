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
        // Jika sudah ada user_id di attributes, gunakan itu
        $userId = null;

        return [
            'user_id' => function (array $attributes) use (&$userId) {
                $userId = $attributes['user_id'] ?? User::inRandomOrder()->whereNot('id', 1)->first()?->id;
                return $userId;
            },
            'jenis_transaksi_id' => function (array $attributes) {
                $userId = $attributes['user_id'];
                $jenis = $attributes['jenis'] ?? $this->faker->randomElement(['Pemasukan', 'Pengeluaran']);
                return JenisTransaksi::where('user_id', $userId)
                    ->where('tipe', $jenis)
                    ->inRandomOrder()
                    ->first()?->id;
            },
            'tanggal' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'buku_kas_id' => function (array $attributes) {
                return $attributes['buku_kas_id'] ?? BukuKas::where('user_id', $attributes['user_id'])->inRandomOrder()->first()?->id;
            },
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
            return [
                'user_id' => $userId,
                'jenis_transaksi_id' => function (array $attributes) use ($userId) {
                    $jenisTransaksi = JenisTransaksi::where('user_id', $userId)
                        ->where('tipe', $attributes['jenis'])
                        ->inRandomOrder()
                        ->first();

                    return $jenisTransaksi ? $jenisTransaksi->id : null;
                },
                'buku_kas_id' => function (array $attributes) use ($userId) {
                    // Jika buku_kas_id sudah diset dari luar (seperti di seeder), gunakan itu
                    if (isset($attributes['buku_kas_id'])) {
                        return $attributes['buku_kas_id'];
                    }

                    $bukuKas = BukuKas::where('user_id', $userId)->inRandomOrder()->first();
                    return $bukuKas ? $bukuKas->id : null;
                },
            ];
        });
    }
}