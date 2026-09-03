<?php

use App\Filament\Resources\PiutangResource;
use App\Filament\Resources\UtangResource;

it('menggunakan ikon navigasi yang sesuai untuk utang dan piutang', function () {
    expect(UtangResource::getNavigationIcon())
        ->toBe('heroicon-o-credit-card')
        ->and(PiutangResource::getNavigationIcon())
        ->toBe('heroicon-o-wallet');
});
