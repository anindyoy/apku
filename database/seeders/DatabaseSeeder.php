<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\TransaksiSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::truncate();

        // User::factory()->create([
        //     'name' => 'Super User',
        //     'email' => 'super@apku.com',
        //     'password' => Hash::make('superapku'),
        // ]);

        // User::factory(10)->create();

        // $this->call([
        //     TransaksiSeeder::class
        // ]);
    }
}
