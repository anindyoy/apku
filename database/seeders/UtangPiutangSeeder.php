<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UtangPiutang;
use Illuminate\Database\Seeder;
use App\Models\UtangPiutangDetail;
use Database\Factories\UtangPiutangDetailFactory;
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
            for ($i = 0; $i < rand(0, 3); $i++) {
                $utang = UtangPiutang::factory()->create([
                    'user_id' => $value->id,
                ]);

                for ($j = 0; $j < rand(1, 5); $j++) {
                    UtangPiutangDetail::factory()->create([
                        'utang_piutang_id' => $utang->id
                    ]);
                }
            }
        }
    }
}
