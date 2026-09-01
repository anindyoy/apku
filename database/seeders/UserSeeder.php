<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            User::truncate();
        });

        User::factory()->create([
            'name' => 'Super User',
            'email' => 'super@apku.com',
            'password' => Hash::make('superapku'),
            'role' => 'super',
        ]);

        User::factory(10)->create();
    }
}
