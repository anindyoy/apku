<?php

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

describe('url panel app', function () {
    it('mendaftarkan panel dengan id dan path app', function () {
        $panel = Filament::getPanel('app');

        expect($panel->getId())->toBe('app')
            ->and($panel->getPath())->toBe('app');
    });

    it('memakai nama route filament.app dan tidak lagi filament.admin', function () {
        expect(Route::has('filament.app.auth.login'))->toBeTrue()
            ->and(Route::has('filament.app.pages.dashboard'))->toBeTrue()
            ->and(Route::has('filament.app.resources.transaksis.index'))->toBeTrue()
            ->and(Route::has('filament.admin.auth.login'))->toBeFalse()
            ->and(Route::has('filament.admin.pages.dashboard'))->toBeFalse();
    });

    it('menghasilkan url dashboard di bawah /app', function () {
        expect(parse_url(Dashboard::getUrl(), PHP_URL_PATH))->toBe('/app');
    });

    it('mengalihkan url lama /admin ke /app secara permanen', function () {
        $this->get('/admin')->assertStatus(301)->assertRedirect('/app');
    });

    it('mengalihkan url lama /admin dengan sub path ke /app', function () {
        $this->get('/admin/transaksi')->assertStatus(301)->assertRedirect('/app/transaksi');
    });

    it('mengalihkan slug pencocokan saldo lama ke halaman cocokkan saldo baru', function () {
        $this->get('/admin/audit-saldo-dompet')->assertStatus(301)->assertRedirect('/app/cocokkan-saldo');
        $this->get('/admin/riwayat-pencocokan')->assertStatus(301)->assertRedirect('/app/cocokkan-saldo');
    });

    it('menampilkan dashboard pada path baru untuk pengguna yang sudah onboarding', function () {
        $user = createRegularUserWithBukuKas();

        $this->actingAs($user)
            ->get('/app')
            ->assertSuccessful();
    });

    it('mengalihkan tamu yang membuka halaman panel ke login /app', function () {
        $this->get('/app')->assertRedirect(route('filament.app.auth.login'));
    });
});
