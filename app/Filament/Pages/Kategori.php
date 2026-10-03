<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use BackedEnum;
use Filament\Pages\Page;

class Kategori extends Page
{
    use HidesFromAdminNavigation;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $title = 'Kategori';

    protected string $view = 'filament.pages.kategori';
}
