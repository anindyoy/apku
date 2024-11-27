<?php

namespace Database\Factories;

use App\Models\UtangPiutang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtangPiutangDetail>
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
            'nominal' => rand(1, 100) . '000',
            'tipe' => rand(0, 1) ? 'tambah' : 'kurang',
            'deskripsi' => fake()->optional()->sentence(),
            // 'tanggal' => fake()->dateTimeBetween('-3 weeks', 'now'),
            'created_at' => fake()->dateTimeBetween('-3 weeks', 'now'),
        ];
    }
}
