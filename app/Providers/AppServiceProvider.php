<?php

namespace App\Providers;

use Filament\Tables\Table;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Actions\CreateAction;

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
        
        Table::configureUsing(function (Table $table): void {
            $table
                ->striped();
        });
    }
}
