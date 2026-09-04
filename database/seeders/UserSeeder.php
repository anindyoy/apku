<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
            'name' => 'Admin',
            'email' => 'admin@apku.com',
            'password' => Hash::make('adminapku'),
            'role' => 'admin',
        ]);

        User::factory(10)->create();
    }
}
