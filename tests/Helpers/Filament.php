<?php

/*
|--------------------------------------------------------------------------
| Filament Test Helpers
|--------------------------------------------------------------------------
|
| Helper bersama untuk test di tests/Feature/Filament. File di direktori
| tests/Helpers di-load otomatis oleh Pest (lihat Pest\Bootstrappers\BootFiles).
|
*/

use App\Models\User;
use App\Models\BukuKas;

if (! function_exists('createRegularUserWithBukuKas')) {
    function createRegularUserWithBukuKas(): User
    {
        $user = User::factory()->create([
            'role' => 'reguler',
            'email_verified_at' => now(),
        ]);

        BukuKas::factory()->create([
            'user_id' => $user->id,
            'nama_buku' => 'Kas Test',
            'saldo' => 100000,
        ]);

        return $user;
    }
}

if (! function_exists('createSuperUser')) {
    function createSuperUser(): User
    {
        return User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'role' => 'super',
            'email_verified_at' => now(),
        ]);
    }
}
