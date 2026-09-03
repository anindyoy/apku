<?php

namespace Database\Factories;

use App\Models\Dompet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Dompet> */
class DompetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama_dompet' => fake()->unique()->word(),
            'saldo' => 0,
            'is_default' => false,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
