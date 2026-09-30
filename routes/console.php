<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn (): int => Artisan::call('masa-aktif:kirim-pengingat'))
    ->name('masa-aktif:kirim-pengingat')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::call(fn (): int => Artisan::call('harga-emas:pantau'))
    ->name('harga-emas:pantau')
    ->daily()
    ->withoutOverlapping();

Schedule::call(fn (): int => Artisan::call('telescope:prune', ['--hours' => 336]))
    ->name('telescope:prune')
    ->daily()
    ->withoutOverlapping();
