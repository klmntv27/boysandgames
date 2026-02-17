<?php

namespace App\Telegram\Commands;

use App\Models\User;
use Telegram\Bot\Commands\Command;

class UsersListCommand extends Command
{
    protected string $name = 'users';

    protected string $description = 'Вывести список пользователей';

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
        $message = "Список пользователей и средний рейтинг:\n";

        $users = User::query()
            ->whereHas('ratings')
            ->withAvg('ratings', 'rating')
            ->orderByDesc('ratings_avg_rating')
            ->get();

        foreach ($users as $user) {
            $message .= sprintf(
                "%s - %.2f\n",
                $user->first_name,
                $user->ratings_avg_rating
            );
        }

        return $message;
    }
}

