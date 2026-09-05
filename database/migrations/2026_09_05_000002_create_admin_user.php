<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Membuat akun admin awal aplikasi.
     */
    public function up(): void
    {
        $password = config('auth.admin_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('ADMIN_PASSWORD wajib diisi sebelum migration dijalankan.');
        }

        DB::table('users')->insertOrIgnore([
            'name' => 'Admin',
            'email' => 'admin@apku.com',
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => 'admin',
            'type' => 'premium',
            'masa_aktif' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Menghapus akun admin awal aplikasi.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin@apku.com')
            ->where('role', 'admin')
            ->delete();
    }
};
