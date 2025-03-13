<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\PrestationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function () {
        return Auth::user();
    });

    Route::prefix('prestations')
        ->as('prestations.')
        ->group(function () {
            Route::get('/', [PrestationController::class, 'index'])
                ->name('index')
                ->middleware('can:viewAny,App\Models\Prestation');

            Route::post('/', [PrestationController::class, 'store'])
                ->name('store')
                ->middleware('can:create,App\Models\Prestation');

            Route::put('/{prestation}', [PrestationController::class, 'update'])
                ->name('update')
                ->middleware('can:update,prestation');
                
            Route::delete('/{prestation}', [PrestationController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:delete,prestation');
        });
    
        Route::prefix('clients')
        ->as('clients.')
        ->group(function () {
            Route::get('/', [ClientController::class, 'index'])
                ->name('index')
                ->middleware('can:viewAny,App\Models\Client');

            Route::post('/', [ClientController::class, 'store'])
                ->name('store')
                ->middleware('can:create,App\Models\Client');

            Route::put('/{client}', [ClientController::class, 'update'])
                ->name('update')
                ->middleware('can:update,client');
                
            Route::delete('/{client}', [ClientController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:delete,client');
        });
});

Route::prefix('auth')
    ->as('auth.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
