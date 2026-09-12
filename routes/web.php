<?php

use App\Http\Controllers\KasPublikController;
use Illuminate\Support\Facades\Route;

Route::get('/kas-publik/{token}', KasPublikController::class)
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('kas.publik');

Route::get('/', function () {
    return redirect(url('/admin'));
    // return view('welcome');
});
