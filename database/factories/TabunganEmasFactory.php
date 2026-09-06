<?php

namespace Database\Factories;

use App\Models\BukuKas;
use Illuminate\Database\Eloquent\Factories\Factory;

class TabunganEmasFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buku_kas_id' => BukuKas::factory(),
            'nama' => 'Tabungan Emas',
            'merek' => null,
            'produk' => null,
            'kadar' => 99.99,
            'berat_gram' => 0,
            'total_modal' => 0,
        ];
    }
}
