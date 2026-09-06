<?php

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function penggunaLoginDeveloper(): array
{
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());

    /** @var FilamentDeveloperLoginsPlugin $plugin */
    $plugin = $panel->getPlugin('filament-developer-logins');

    return $plugin->getUsers();
}

test('akun admin tidak tersedia pada login developer ketika aplikasi demo', function () {
    config()->set('app.demo', true);

    User::where('email', 'admin@apku.com')->update(['role' => 'admin']);

    expect(penggunaLoginDeveloper())
        ->not->toHaveKey('Admin')
        ->not->toContain('admin@apku.com');
});

test('akun admin tersedia pada login developer ketika aplikasi bukan demo', function () {
    config()->set('app.demo', false);

    User::where('email', 'admin@apku.com')->update(['role' => 'admin']);

    expect(penggunaLoginDeveloper())->toHaveKey('Admin', 'admin@apku.com');
});
