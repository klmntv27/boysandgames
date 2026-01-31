<?php

namespace App\Telegram\Commands;

use App\Jobs\GetGamePricesJob;
use App\Jobs\GetGameReviewsJob;
use App\Jobs\RequestGameRatingJob;
use App\Models\Game;
use App\Models\User;
use App\Services\SteamApiGameInfoService;
use Bus;
use Telegram\Bot\Commands\Command;

class AddGameCommand extends Command
{
    protected string $name = 'add';

    protected string $description = 'Добавить игру. Использование: /add <ссылка на Steam>';

    public function __construct(
        private readonly SteamApiGameInfoService $steamApiService
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

        Bus::chain([
            new GetGamePricesJob($game),
            new GetGameReviewsJob($game),
            new RequestGameRatingJob($game)
        ])->dispatch();

        $this->replyWithMessage([
            'text' => "✅ Игра \"$game->name\" успешно добавлена!",
        ]);
    }
}

