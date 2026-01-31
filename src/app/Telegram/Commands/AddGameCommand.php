<?php

namespace App\Telegram\Commands;

use App\Models\Game;
use App\Models\User;
use App\Services\SteamApiService;
use Telegram\Bot\Commands\Command;

class AddGameCommand extends Command
{
    protected string $name = 'add';

    protected string $description = 'Добавить игру. Использование: /add <ссылка на Steam>';

    public function __construct(
        private readonly SteamApiService $steamApiService
    ) {}

    public function handle(): void
    {
        $text = $this->getUpdate()->getMessage()->getText();

        $appId = $this->steamApiService->extractAppIdFromUrl($text);

        if ($appId === null) {
            $this->replyWithMessage([
                'text' => '❌ Не удалось найти ссылку на Steam игру. Пожалуйста, отправьте команду в формате: /add https://store.steampowered.com/app/123456',
            ]);
            return;
        }

        if (Game::where('steam_id', $appId)->exists()) {
            $this->replyWithMessage([
                'text' => '⚠️ Эта игра уже добавлена.',
            ]);
            return;
        }

        $gameDetails = $this->steamApiService->getGameDetails($appId);

        if ($gameDetails === null) {
            $this->replyWithMessage([
                'text' => '❌ Не удалось получить информацию об игре из Steam.',
            ]);
            return;
        }

        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::firstOrCreate(
            ['telegram_id' => $from->getId()],
            [
                'first_name' => $from->getFirstName(),
                'last_name' => $from->getLastName(),
                'nickname' => $from->getUsername(),
            ]
        );

        $game = Game::create([
            'initiator_id' => $user->id,
            ...$gameDetails,
        ]);

        $this->replyWithMessage([
            'text' => "✅ Игра \"{$game->name}\" успешно добавлена!",
        ]);
    }
}

