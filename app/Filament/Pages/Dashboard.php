<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TransaksiResource;
use App\Filament\Widgets\AdminOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->redirect(TransaksiResource::getUrl('index'), navigate: true);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Admin';
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [AdminOverview::class];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
