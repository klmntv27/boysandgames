<?php

use App\Http\Controllers\TelegramController;
use App\Http\Middleware\EnsureUserIsRegisteredMiddleware;

Route::middleware(EnsureUserIsRegisteredMiddleware::class)->post('bot', TelegramController::class);
