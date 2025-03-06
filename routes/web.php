<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app'); // Charge Vue.js
})->where('any', '^(?!api).*$'); // Exclure /api/*
