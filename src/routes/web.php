<?php

use App\Http\Middleware\TelegramMiniappMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([TelegramMiniappMiddleware::class])->group(function () {
    Route::get('/miniapp', function () {
        return view('components.telegram-miniapp');
    })->name('miniapp');

    Route::get('/miniapp/game/{id}', function ($id) {
        return view('components.game-detail', ['gameId' => $id]);
    })->name('miniapp.game');

    // API endpoints
    Route::prefix('api/miniapp')->name('api.miniapp.')->group(function () {
        Route::get('/games', [\App\Http\Controllers\MiniappController::class, 'games'])->name('games');
        Route::get('/games/{id}', [\App\Http\Controllers\MiniappController::class, 'gameDetail'])->name('game.detail');
        Route::get('/games/{id}/ratings', [\App\Http\Controllers\MiniappController::class, 'gameRatings'])->name('game.ratings');
        Route::post('/games/{id}/rate', [\App\Http\Controllers\MiniappController::class, 'rateGame'])->name('game.rate');
        Route::put('/user/currency', [\App\Http\Controllers\MiniappController::class, 'updateCurrency'])->name('user.currency');
    });
});
