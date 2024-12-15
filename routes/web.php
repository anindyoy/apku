<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\UtangPiutangDetail;

Route::get('/', function () {
    return redirect(url('/admin'));
    // return view('welcome');
});

// Route::get('/admin/utang-piutang-detail/{id}', UtangPiutangDetail::class);
