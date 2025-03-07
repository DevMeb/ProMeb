<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FactureController;
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
    Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');
    Route::post('/factures', [FactureController::class, 'store'])->name('factures.store');
    Route::delete('/factures/{id}', [FactureController::class, 'destroy'])->name('factures.destroy');
});