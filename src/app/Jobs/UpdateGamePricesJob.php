<?php

namespace App\Jobs;

use App\Dto\GamePriceDto;
use App\Enums\CurrencyEnum;
use App\Helpers\MarkdownHelper;
use App\Models\Price;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateGamePricesJob extends GetGamePricesJob implements ShouldQueue
{
    protected const MIN_GAME_RATING_FOR_ALERT = 3.5;

    public function handle(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            usleep(self::RATE_LIMIT_DELAY * 1000);

            $currentLocalPrice = $this->getCurrentPrice($currency);
            $currentSteamPrice = $this->fetchPrice($currency);

            if (is_null($currentSteamPrice)) {
                continue;
            }
            $this->updatePrice($currentSteamPrice, $currency);

            if (is_null($currentLocalPrice)) {
                continue;
            }
            $this->alertIfPriceDropped($currentLocalPrice, $currentSteamPrice, $currency);
        }
    }

    protected function getCurrentPrice(CurrencyEnum $currency): ?GamePriceDto
    {
        $priceData = Price::where('game_id', $this->game->id)
            ->where('currency', $currency->value)
            ->first();

        if (is_null($priceData)) {
            return null;
        }

        return new GamePriceDto(
            $priceData->initial_price,
            $priceData->final_price,
            $priceData->discount_percent,
        );
    }

    protected function alertIfPriceDropped(
        GamePriceDto $oldPrice,
        GamePriceDto $newPrice,
        CurrencyEnum $currency,
    ): void {
        if ($newPrice->discountPrice >= $oldPrice->discountPrice) {
            return;
        }

        $gameRating = $this->game->average_rating;
        if (is_null($gameRating)) {
            return;
        }

        if ($gameRating < self::MIN_GAME_RATING_FOR_ALERT) {
            return;
        }

        NotifyPriceDroppedJob::dispatch(
            $this->game,
            $oldPrice->discountPrice,
            $newPrice->discountPrice,
            $currency,
            new MarkdownHelper()
        );
    }
}
