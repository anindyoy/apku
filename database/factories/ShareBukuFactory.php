<?php

namespace Database\Factories;

use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShareBuku>
 */
class ShareBukuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buku_kas_id' => BukuKas::factory(),
            'user_id' => User::factory(),
            'invited_by_user_id' => fn (array $attributes) => BukuKas::withoutGlobalScopes()->find($attributes['buku_kas_id'])?->user_id,
            'privilege' => fake()->randomElement(['viewer', 'editor']),
            'berlaku_mulai' => now(),
            'berlaku_sampai' => null,
        ];
    }
}
