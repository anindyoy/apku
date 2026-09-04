<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserHasCompletedOnboarding;
use App\Models\User;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Langganan')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Utang Piutang')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Pengaturan')
                    ->collapsed(true),
            ])
            ->profile()
            ->registration()
            ->emailVerification()
            ->passwordReset()
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): View => view('filament.components.navbar-user-name'),
            )
            ->plugins([
                SpotlightPlugin::make(),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users(function () {
                        $users = [];

                        // Tambahkan admin@apku.com sebagai Admin jika tersedia.
                        $admin = User::where('email', 'admin@apku.com')->first();
                        if ($admin) {
                            $users['Admin'] = $admin->email;
                        }

                        // Tambahkan pengguna dengan transaksi terbanyak.
                        $topUser = User::withCount('transaksi')
                            ->orderBy('transaksi_count', 'desc')
                            ->first();

                        if ($topUser && (! isset($users['Admin']) || $topUser->email !== $admin?->email)) {
                            $users['User'] = $topUser->email;
                        }

                        return $users;
                    }),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserHasCompletedOnboarding::class,
            ]);
    }
}
