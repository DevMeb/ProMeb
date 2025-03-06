<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PrestationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')
    ->as('auth.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('prestations', PrestationController::class);
});