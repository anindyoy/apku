<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Mengisi data pengguna contoh.
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
            'type' => 'premium',
            'masa_aktif' => now()->addYear(),
        ]);

        User::factory()->create([
            'name' => 'Pengguna Reguler',
            'email' => 'reguler@apku.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'type' => 'reguler',
            'masa_aktif' => null,
        ]);

        User::factory()->create([
            'name' => 'Pengguna Premium',
            'email' => 'premium@apku.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'type' => 'premium',
            'masa_aktif' => now()->addMonths(6),
        ]);

        User::factory(8)->create(['role' => 'user']);
    }
}
