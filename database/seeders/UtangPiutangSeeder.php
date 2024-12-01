<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UtangPiutang;
use Illuminate\Database\Seeder;
use App\Models\UtangPiutangDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UtangPiutangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UtangPiutang::truncate();
        UtangPiutangDetail::truncate();

        $users = User::whereNot('id', 1)->get();

        foreach ($users as $value) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                $utang = UtangPiutang::factory()->create([
                    'user_id' => $value->id,
                ]);

                $total = rand(1, 10) . '0000';
                $jumlah_data = rand(2, 3);
                $nominals = []; // Array to store nominal values
                $remaining = $total; // Keep track of the remaining amount

                for ($j = 0; $j < $jumlah_data - 1; $j++) { // Loop one less time
                    if ($remaining == 0) {
                        continue;
                    }

                    $nominal = rand(1, floor($remaining / 1000)) * 1000; // Ensure nominal is a multiple of 1000

                    if ($nominal > $remaining) {
                        $nominal = $remaining;
                    }

                    $nominals[] = $nominal;
                    $remaining -= $nominal;
                }

                if ($remaining) {
                    $nominals[] = $remaining; // Last nominal is the remainder
                }

                foreach ($nominals as $key => $nominal) {
                    UtangPiutangDetail::factory()->create([
                        'nominal' => $nominal, //Use nominals array
                        'tipe' => 'tambah',
                        'created_at' => fake()->dateTimeBetween($utang->created_at, 'now'),
                        'utang_piutang_id' => $utang->id
                    ]);
                }

                UtangPiutangDetail::factory()->create([
                    'nominal' => rand(1, 3) == 1 ? $total : rand(1, 9) . '0000',
                    'created_at' => fake()->dateTimeBetween($utang->utang_piutang_detail()->latest()->first()->created_at, 'now'),
                    'tipe' => 'kurang',
                    'utang_piutang_id' => $utang->id
                ]);
            }
        }
    }
}
