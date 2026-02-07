<?php

use App\Jobs\GetGamePricesJob;
use App\Models\Game;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Game::all()->each(fn(Game $game) => GetGamePricesJob::dispatch($game));
})->hourly();
