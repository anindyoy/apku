<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn (): int => Artisan::call('masa-aktif:kirim-pengingat'))
    ->name('masa-aktif:kirim-pengingat')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::call(fn (): int => Artisan::call('telescope:prune', ['--hours' => 336]))
    ->name('telescope:prune')
    ->daily()
    ->withoutOverlapping();
