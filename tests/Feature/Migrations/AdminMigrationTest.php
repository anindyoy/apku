<?php

use App\Models\TabunganEmas;
use App\Models\TransaksiEmas;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('migration membuat akun admin menggunakan password dari environment', function () {
    $admin = User::query()->where('email', 'admin@apku.com')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and($admin->type)->toBe('premium')
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password-admin-testing', $admin->password))->toBeTrue();
});

test('user seeder tidak membuat ulang atau menghapus akun admin dari migration', function () {
    $adminSebelumSeeder = User::query()->where('email', 'admin@apku.com')->firstOrFail();

    expect(TabunganEmas::count())->toBeGreaterThan(0)
        ->and(TransaksiEmas::count())->toBeGreaterThan(0);

    $this->seed(UserSeeder::class);

    $adminSetelahSeeder = User::query()->where('email', 'admin@apku.com')->firstOrFail();

    expect($adminSetelahSeeder->id)->toBe($adminSebelumSeeder->id)
        ->and($adminSetelahSeeder->password)->toBe($adminSebelumSeeder->password)
        ->and(User::admin()->count())->toBe(1)
        ->and(User::notAdmin()->count())->toBe(10)
        ->and(TabunganEmas::count())->toBe(0)
        ->and(TransaksiEmas::count())->toBe(0);
});
