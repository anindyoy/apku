<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MetodePembayaranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Transfer '.$this->faker->unique()->company(),
            'jenis' => 'bank',
            'nama_penyedia' => $this->faker->company(),
            'nomor_tujuan' => $this->faker->numerify('##########'),
            'nama_pemilik' => $this->faker->name(),
            'instruksi' => 'Transfer sesuai total yang tertera pada order.',
            'is_active' => true,
            'urutan' => 0,
        ];
    }
}
