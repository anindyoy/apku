<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaketLanggananFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Premium '.$this->faker->unique()->numberBetween(1, 9999),
            'harga' => $this->faker->numberBetween(10000, 500000),
            'durasi_hari' => $this->faker->randomElement([30, 90, 365]),
            'is_active' => true,
        ];
    }
}
