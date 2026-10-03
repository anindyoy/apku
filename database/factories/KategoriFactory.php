<?php

namespace Database\Factories;

use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\User;
use App\Services\KategoriService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama' => fake()->randomElement(['Pemasukan', 'Pengeluaran']),
        ];
    }

    /** Menghubungkan kategori ke kas setelah dibuat agar dapat dipakai transaksi kas tersebut. */
    public function untukKas(BukuKas ...$kas): static
    {
        return $this->afterCreating(function (Kategori $kategori) use ($kas): void {
            app(KategoriService::class)->hubungkan($kategori, array_map(fn (BukuKas $item): int => $item->id, $kas));
        });
    }
}
