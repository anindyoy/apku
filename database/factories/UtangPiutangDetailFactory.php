<?php

namespace Database\Factories;

use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UtangPiutangDetail>
 */
class UtangPiutangDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'utang_piutang_id' => UtangPiutang::inRandomOrder()->first()->id,
            'nominal' => rand(1, 100) * 1000,
            'tipe' => rand(0, 1) ? 'tambah' : 'kurang',
            'deskripsi' => fake()->optional()->sentence(),
            'created_at' => fake()->dateTimeBetween('-3 weeks', 'now'),
        ];
    }
}
