<?php

namespace App\Providers;

use App\Services\DashboardCache;
use Filament\Tables\Table;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('id');

        // Arahkan tamu yang membuka halaman non-Filament ke login admin karena tidak ada route login bawaan.
        Authenticate::redirectUsing(fn (Request $request): ?string => route('filament.admin.auth.login'));

        DB::listen(DashboardCache::invalidateWrite(...));

        Table::configureUsing(function (Table $table): void {
            if (! app()->runningUnitTests()) {
                $table->deferLoading();
            }

            $table->striped();
        });
    }
}
