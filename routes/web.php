<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(url('/admin'));
    // return view('welcome');
});
