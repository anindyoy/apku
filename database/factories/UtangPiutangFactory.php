<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtangPiutang>
 */
class UtangPiutangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::notSuper()->inRandomOrder()->first()->id,
            'code' => uniqid(),
            'kepada' => fake()->name(),
            'tipe' => rand(0, 1) ? 'utang' : 'piutang',
            'sambung_kas' => rand(0, 1),
            'tempo' => rand(0, 1) ? fake()->dateTimeBetween('+1 day', '+1 month') : null,
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
