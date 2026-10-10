<?php

namespace Database\Factories;

use App\Enums\StatusTrialPremium;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrialPremium>
 */
class TrialPremiumFactory extends Factory
{
    public function definition(): array
    {
        $mulai = today();

        return [
            'user_id' => User::factory(),
            'hp_normal' => '08'.fake()->unique()->numerify('##########'),
            'email_normal' => fake()->unique()->safeEmail(),
            'mulai_pada' => $mulai->toDateString(),
            'berakhir_pada' => $mulai->copy()->addDays(29)->toDateString(),
            'status' => StatusTrialPremium::Aktif,
        ];
    }
}
