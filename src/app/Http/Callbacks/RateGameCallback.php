<?php

namespace App\Http\Callbacks;

use App\Enums\UserRatingEnum;
use App\Jobs\NotifyAboutNewRatingJob;
use App\Models\Game;
use App\Models\Rating;
use App\Models\User;
use Telegram\Bot\Exceptions\TelegramResponseException;

class RateGameCallback extends Callback
{
    public function handle(...$args): void
    {
        $gameId = (int)$args[0];
        $ratingValue = (int)$args[1];

        $game = Game::find($gameId);
        $user = User::where('telegram_id', $this->from->getId())->first();

        if (!$game || !$user) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $this->callbackQuery->getId(),
                'text' => 'Ошибка: игра или пользователь не найдены',
            ]);
            return;
        }

        $rating = UserRatingEnum::from($ratingValue);

        $rating = Rating::updateOrCreate(
            [
                'game_id' => $gameId,
                'user_id' => $user->id,
            ],
            [
                'rating' => $rating,
            ]
        );

        NotifyAboutNewRatingJob::dispatch($rating);

        try {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $this->callbackQuery->getId(),
                'text' => sprintf(
                    'Вы оценили игру "%s" на %d %s',
                    $game->name,
                    $rating->rating->value,
                    $rating->rating->emoji()
                ),
            ]);
        } catch (TelegramResponseException $e) {
            $this->send([
                'text' => sprintf('✅ Вы оценили игру: %d %s', $rating->value, $rating->emoji()),
            ]);
        }
    }
}
