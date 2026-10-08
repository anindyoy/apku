<?php

namespace Database\Factories;

use App\Models\SumberDana;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SumberDana> */
class SumberDanaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama_dompet' => fake()->unique()->word(),
            'jenis' => 'tunai',
            'saldo' => 0,
            'is_default' => false,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
