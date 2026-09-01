<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('masa-aktif:kirim-pengingat')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('telescope:prune --hours=336')
    ->daily()
    ->withoutOverlapping();
