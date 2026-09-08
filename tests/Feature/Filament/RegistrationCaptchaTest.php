<?php

use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.turnstile.site_key', 'kunci-situs-pengujian');
    config()->set('services.turnstile.secret_key', 'kunci-rahasia-pengujian');
    config()->set('services.turnstile.hostnames', ['localhost']);
});

test('pendaftaran ditolak ketika turnstile menolak token', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Pengguna Baru',
            'email' => 'pengguna.baru@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'turnstile_token' => 'token-tidak-valid',
        ])
        ->call('register')
        ->assertHasErrors(['data.turnstile_token']);

    expect(User::where('email', 'pengguna.baru@example.com')->exists())->toBeFalse();
});

test('pendaftaran diterima ketika token turnstile valid', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => true,
            'hostname' => 'localhost',
            'action' => 'register',
        ]),
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Pengguna Turnstile',
            'email' => 'pengguna.turnstile@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'turnstile_token' => 'token-valid',
        ])
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'pengguna.turnstile@example.com')->exists())->toBeTrue();

    Http::assertSent(fn ($request): bool => $request['secret'] === 'kunci-rahasia-pengujian'
        && $request['response'] === 'token-valid');
});
