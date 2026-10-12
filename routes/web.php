<?php

use App\Http\Controllers\BeliPaketController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KasPublikController;
use App\Http\Controllers\LanggananInvoiceController;
use App\Http\Controllers\TutorialController;
use Illuminate\Support\Facades\Route;

Route::get('/tutorial', TutorialController::class)->name('tutorial');

Route::get('/beli/{paket}', BeliPaketController::class)
    ->whereNumber('paket')
    ->middleware('throttle:60,1')
    ->name('beli-paket');

Route::middleware(['auth', 'verified'])
    ->get('/langganan/{langganan}/invoice', LanggananInvoiceController::class)
    ->name('langganan.invoice');

Route::get('/kas-publik/{token}', KasPublikController::class)
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('kas.publik');

Route::get('/', HomeController::class)->name('home');
