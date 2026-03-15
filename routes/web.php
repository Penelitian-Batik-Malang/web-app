<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
});

Route::get('/galeri', function () {
    return view('pages.galeri');
})->name('galeri');

Route::get('/fitur', function () {
    return view('pages.features');
})->name('fitur');

Route::get('/login', function () {
    return view('pages.login');
})->name('login');
