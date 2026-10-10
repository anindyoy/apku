<?php

// Daftarkan Telescope hanya jika paketnya terpasang (require-dev di lokal).
$telescopeProviders = class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
    ? [App\Providers\TelescopeServiceProvider::class]
    : [];

return array_merge([
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
], $telescopeProviders);
 