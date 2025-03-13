<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\FactureController;
use App\Http\Controllers\API\PrestationController;
use App\Http\Controllers\API\TauxHoraireController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('user')
        ->as('user.')
        ->group(function () {
            Route::get('/', [UserController::class, 'show'])->name('show');
            Route::put('/', [UserController::class, 'update'])->name('update');
        });

    Route::prefix('prestations')
        ->as('prestations.')
        ->group(function () {
            Route::get('/', [PrestationController::class, 'index'])
                ->name('index');

            Route::post('/', [PrestationController::class, 'store'])
                ->name('store');

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
                ->name('index');

            Route::post('/', [ClientController::class, 'store'])
                ->name('store');

            Route::put('/{client}', [ClientController::class, 'update'])
                ->name('update')
                ->middleware('can:update,client');
                
            Route::delete('/{client}', [ClientController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:delete,client');
        });

        Route::prefix('taux-horaires')
        ->as('taux-horaires.')
        ->group(function () {
            Route::get('/', [TauxHoraireController::class, 'index'])
                ->name('index');

            Route::post('/', [TauxHoraireController::class, 'store'])
                ->name('store');

            Route::put('/{tauxHoraire}', [TauxHoraireController::class, 'update'])
                ->name('update')
                ->middleware('can:update,tauxHoraire');
                
            Route::delete('/{tauxHoraire}', [TauxHoraireController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:delete,tauxHoraire');
        });

        
});

Route::prefix('auth')
    ->as('auth.')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
