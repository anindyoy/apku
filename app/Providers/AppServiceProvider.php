<?php

namespace App\Providers;

use App\Services\DashboardCache;
use Filament\Tables\Table;
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
        DB::listen(DashboardCache::invalidateWrite(...));

        Table::configureUsing(function (Table $table): void {
            $table
                ->striped();
        });
    }
}
