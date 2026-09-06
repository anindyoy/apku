<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HidesFromAdminNavigation;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Kategori extends Page
{
    use HidesFromAdminNavigation;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Aktivitas';

    protected static ?string $title = 'Aktivitas';

    protected string $view = 'filament.pages.kategori';
}
