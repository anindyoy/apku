<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Promo '.$this->faker->unique()->word(),
            'masa_aktif' => today()->addMonth(),
            'jumlah_diskon' => $this->faker->numberBetween(1, 100),
            'dapat_dipakai_berulang' => false,
        ];
    }
}
