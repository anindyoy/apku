<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureUserHasCompletedOnboarding;
use App\Models\User;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
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
            ->brandName('APKu')
            // Logo di atas menu sidebar memakai wordmark dompet yang sama dengan landing page.
            ->brandLogo(asset('logo-options/apku-dompet-wordmark.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('logo-options/apku-dompet.svg'))
            ->maxContentWidth(Width::Full)
            ->login()
            ->navigationItems([
                NavigationItem::make('Tutorial Penggunaan')
                    ->url(fn (): string => route('tutorial'))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-book-open')
                    ->sort(100)
                    ->visible(fn (): bool => ! auth()->user()?->isAdmin()),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): View => view('filament.components.tutorial-link'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.components.global-styles'),
            )
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Utang Piutang')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Laporan')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Langganan')
                    ->collapsed(false),
            ])
            ->profile()
            ->registration(Register::class)
            ->emailVerification()
            ->passwordReset()
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): View => view('filament.components.navbar-user-name'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): View => view('filament.components.topbar-tutorial'),
            )
            ->plugins([
                SpotlightPlugin::make(),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users(function () {
                        $users = [];
                        $appDemo = config('app.demo', false);

                        // Tambahkan admin@apku.com sebagai Admin jika tersedia.
                        $admin = User::where('email', 'admin@apku.com')->first();
                        if ($admin && ! $appDemo) {
                            $users['Admin'] = $admin->email;
                        }

                        // Tambahkan pengguna dengan transaksi terbanyak.
                        $topUser = User::query()
                            ->when($appDemo, fn ($query) => $query->notAdmin())
                            ->withCount('transaksi')
                            ->orderBy('transaksi_count', 'desc')
                            ->first();

                        if ($topUser && (! isset($users['Admin']) || $topUser->email !== $admin?->email)) {
                            $users['Pengguna'] = $topUser->email;
                        }

                        return $users;
                    }),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
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
