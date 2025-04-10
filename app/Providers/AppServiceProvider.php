<?php

namespace App\Providers;

use Filament\Tables\Table;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Facades\FilamentAsset;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

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

        DB::prohibitDestructiveCommands(app()->isProduction());

        FilamentAsset::register([
            Js::make('custom-script', 'https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js'),
            Css::make('custom-stylesheet', 'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css'),
        ]);
    }
}
