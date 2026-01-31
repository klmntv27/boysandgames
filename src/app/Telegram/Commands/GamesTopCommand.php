<?php

namespace App\Telegram\Commands;

use App\Models\Game;
use Telegram\Bot\Commands\Command;

class GamesTopCommand extends Command
{
    protected string $name = 'top';

    protected string $description = 'Вывести топ игр';

    public function handle(): void
    {
        $this->replyWithMessage([
            'text' => $this->buildMessage(),
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ]);
    }

    protected function buildMessage(): string
    {
        $message = "Топ игр:\n";

        $games = Game::query()
            ->whereHas('ratings')
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->orderByDesc('ratings_avg_rating')
            ->get();

        foreach ($games as $index => $game) {
            $message .= sprintf(
                "%d. [%s](%s) - %s (👥 %s) \n",
                $index + 1,
                $game->name,
                $game->steam_url,
                $game->ratings_avg_rating ? round($game->ratings_avg_rating, 2) : 'N/A',
                $game->ratings_count
            );
        }

        return $message;
    }
}

