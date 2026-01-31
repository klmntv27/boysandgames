<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\SteamApiReviewsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class GetGameReviewsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Game $game
    ) {}

    public function handle(SteamApiReviewsService $reviewsService): void
    {
        $rating = $reviewsService->getGameRating($this->game->steam_id);

        if(is_null($rating)) {
            Log::warning("Could not fetch rating for game {$this->game->steam_id}");
        }

        $this->game->update(['steam_rating' => $rating]);
        Log::info("Game {$this->game->steam_id} rating updated: $rating%");
    }
}
