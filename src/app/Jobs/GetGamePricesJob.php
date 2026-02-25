<?php

namespace App\Jobs;

use App\Dto\GamePriceDto;
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

    protected const BASE_URL = 'https://store.steampowered.com/api/appdetails';
    protected const RATE_LIMIT_DELAY = 1500;

    public function __construct(
        protected Game $game,
    ) {
    }

    public function handle(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            $priceData = $this->fetchPrice($currency);
            if (!is_null($priceData)) {
                $this->updatePrice($priceData, $currency);
            }

            usleep(self::RATE_LIMIT_DELAY * 1000);
        }
    }

    protected function fetchPrice(CurrencyEnum $currency): ?GamePriceDto
    {
        $response = Http::get(self::BASE_URL, [
            'appids' => $this->game->steam_id,
            'cc' => $currency->code(),
        ]);

        if (!$response->successful()) {
            Log::error("Failed to fetch price for game {$this->game->steam_id} in {$currency->title()}");

            return null;
        }

        $data = $response->json();

        if (!isset($data[$this->game->steam_id]['success']) || !$data[$this->game->steam_id]['success']) {
            Log::warning("Steam API returned unsuccessful response for game {$this->game->steam_id} in {$currency->title()}");

            return null;
        }

        $gameData = $data[$this->game->steam_id]['data'];

        if (!isset($gameData['price_overview'])) {
            Log::info("Game {$this->game->steam_id} is free or has no price in {$currency->title()}");

            return null;
        }

        $priceOverview = $gameData['price_overview'];

        return new GamePriceDto(
            $priceOverview['initial'] / 100,
            $priceOverview['final'] / 100,
            $priceOverview['discount_percent'],
        );
    }

    protected function updatePrice(GamePriceDto $priceData, CurrencyEnum $currency): void
    {
        Price::updateOrCreate(
            [
                'game_id' => $this->game->id,
                'currency' => $currency->value,
            ],
            $priceData->toArray(),
        );

        Log::info("Price updated for game {$this->game->steam_id} in {$currency->title()}: {$priceData->discountPrice} cents");
    }
}
