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
            'label' => 'Tabungan Emas',
            'berat_gram' => 0,
            'total_modal' => 0,
        ];
    }
}
