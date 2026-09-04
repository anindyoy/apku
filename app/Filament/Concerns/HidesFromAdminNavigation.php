<?php

namespace App\Filament\Concerns;

trait HidesFromAdminNavigation
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! auth()->user()?->isAdmin();
    }
}
