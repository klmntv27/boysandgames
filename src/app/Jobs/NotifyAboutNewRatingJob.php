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
        $users = User::all()->except($this->user->id);

        foreach ($users as $user) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => $this->buildMessage(),
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true,
            ]);
        }
    }

    protected function buildMessage()
    {
        return sprintf(
            "%s \\(@%s\\) оценил игру [%s](%s)\n\nОценка: %s %s \\(%s\\)\n\nСредний рейтинг игры: %s",
            $this->escapeMarkdownV2($this->user->first_name),
            $this->user->nickname,
            $this->escapeMarkdownV2($this->game->name),
            $this->escapeMarkdownV2($this->game->steam_url),
            $this->escapeMarkdownV2($this->rating->rating->title()),
            $this->rating->rating->emoji(),
            $this->escapeMarkdownV2((string)$this->rating->rating->value),
            $this->escapeMarkdownV2((string)round($this->game->averageRating ?? 0.0, 1)),
        );
    }

    protected function escapeMarkdownV2(string $text): string
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($specialChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }
}
