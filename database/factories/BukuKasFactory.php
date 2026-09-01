<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BukuKas>
 */
class BukuKasFactory extends Factory
{
    /**
     * Menentukan data bawaan model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $goal = rand(0, 4);
        return [
            'user_id' => User::inRandomOrder()->notSuper()->first()->id,
            'nama_buku' => fake()->word() . '-' . fake()->unique()->numerify('########'),
            'saldo' => 0,
            'description' => fake()->sentence(),
            'goal' => $goal == 4 ? rand(100, 1000) : null,
            'tanggal_goal' => $goal == 4 ? fake()->dateTimeBetween('now', '+1 year') : null,
        ];
    }
}
