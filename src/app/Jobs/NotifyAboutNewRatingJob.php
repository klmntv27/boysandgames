<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;

class NotifyAboutNewRatingJob implements ShouldQueue
{
    use Queueable;

    protected Game $game;

    protected User $user;

    public function __construct(
        protected Rating $rating
    )
    {
        $this->game = $rating->game;
        $this->user = $rating->user;
    }

    public function handle(): void
    {
        $gameInitiator = $this->game->initiator;

        Telegram::sendMessage([
            'chat_id' => $gameInitiator->telegram_id,
            'text' => $this->buildMessage(),
        ]);
    }

    protected function buildMessage()
    {
        return sprintf(
            "%s (@%s) оценил игру \"%s\"\n\nОценка: %s %s (%d)\n\nСредний рейтинг игры: %s",
            $this->user->first_name,
            $this->user->nickname,
            $this->game->name,
            $this->rating->rating->title(),
            $this->rating->rating->emoji(),
            $this->rating->rating->value,
            round($this->game->averageRating ?? 0.0, 1),
        );
    }
}
