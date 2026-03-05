<?php

namespace App\Jobs;

use App\Enums\CurrencyEnum;
use App\Models\Game;
use App\Models\Price;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetGamePricesJob implements ShouldQueue
{
    use Queueable;

    private const BASE_URL = 'https://store.steampowered.com/api/appdetails';
    private const RATE_LIMIT_DELAY = 1500;

    public function __construct(
        protected Game $game
    ) {}

    public function handle(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            $this->fetchAndSavePrice($currency);
            usleep(self::RATE_LIMIT_DELAY * 1000);
        }
    }

    private function fetchAndSavePrice(CurrencyEnum $currency): void
    {
        $response = Http::get(self::BASE_URL, [
            'appids' => $this->game->steam_id,
            'cc' => $currency->code(),
        ]);

        if (!$response->successful()) {
            Log::error("Failed to fetch price for game {$this->game->steam_id} in {$currency->title()}");
            return;
        }

        $data = $response->json();

        if (!isset($data[$this->game->steam_id]['success']) || !$data[$this->game->steam_id]['success']) {
            Log::warning("Steam API returned unsuccessful response for game {$this->game->steam_id} in {$currency->title()}");
            return;
        }

        $gameData = $data[$this->game->steam_id]['data'];

        if (!isset($gameData['price_overview'])) {
            Log::info("Game {$this->game->steam_id} is free or has no price in {$currency->title()}");
            return;
        }

        $priceOverview = $gameData['price_overview'];

        Price::updateOrCreate(
            [
                'game_id' => $this->game->id,
                'currency' => $currency->value,
            ],
            [
                'initial_price' => $priceOverview['initial'] / 100,
                'final_price' => $priceOverview['final'] / 100,
                'discount_percent' => $priceOverview['discount_percent'],
            ]
        );

        Log::info("Price updated for game {$this->game->steam_id} in {$currency->title()}: {$priceOverview['final']} cents");
    }
}
