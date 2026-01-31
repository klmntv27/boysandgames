<?php

namespace App\Jobs;

use App\Enums\UserRatingEnum;
use App\Helpers\MarkdownHelper;
use App\Models\Game;
use App\Models\User;
use App\Services\GameSteamReviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class RequestGameRatingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Game                   $game,
        protected GameSteamReviewService $steamReviewService,
        protected MarkdownHelper         $markdownHelper,
    )
    {
    }

    public function handle(): void
    {
        $initiator = $this->game->initiator;
        $mailingList = User::all()->except($initiator->id);

        foreach ($mailingList as $user) {
            $this->requestReview($user);
        }
    }

    protected function requestReview(User $user): void
    {
        if ($this->game->images->isNotEmpty()) {
            $this->sendWithImages($user);
        } else {
            $this->sendWithoutImages($user);
        }
    }

    protected function sendWithImages(User $user): void
    {
        foreach ($this->game->images as $index => $image) {
            $item = [
                'type' => 'photo',
                'media' => $image->url,
            ];

            if ($index === 0) {
                $item['caption'] = $this->buildMessage($user);
                $item['parse_mode'] = 'MarkdownV2';
            }

            $mediaGroup[] = $item;
        }

        Telegram::sendMediaGroup([
            'chat_id' => $user->telegram_id,
            'media' => json_encode($mediaGroup ?? []),
        ]);

        $this->sendRatingRequestMessage($user);
    }

    protected function sendRatingRequestMessage(User $user): void
    {
        $message = "Будем играть в {$this->game->name}? \n";
        foreach (UserRatingEnum::cases() as $rating) {
            $message .= sprintf(
                "%s %s - %s\n",
                $rating->emoji(),
                $rating->value,
                $rating->title()
            );
        }

        Telegram::sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => $message,
            'reply_markup' => $this->buildRatingKeyboard(),
        ]);
    }

    protected function sendWithoutImages(User $user): void
    {
        Telegram::sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => $this->buildMessage($user),
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => false,
            'reply_markup' => $this->buildRatingKeyboard(),
        ]);
    }

    protected function buildMessage(User $user): string
    {
        $message = sprintf(
            "%s \\(@%s\\) предлагает поиграть в [%s](%s)\n\n",
            $this->markdownHelper->escapeMarkdownV2($this->game->initiator->first_name),
            $this->markdownHelper->escapeMarkdownV2($this->game->initiator->nickname),
            $this->markdownHelper->escapeMarkdownV2($this->game->name),
            $this->game->steam_url
        );

        $message .= $this->buildDescription();
        $message .= $this->buildSteamRating();
        $message .= $this->buildPrice($user);

        return $message;
    }

    protected function buildDescription(): string
    {
        if (is_null($this->game->description)) {
            return '';
        }

        $lines = explode("\n", $this->markdownHelper->escapeMarkdownV2($this->game->description));
        $quotedLines = array_map(fn($line) => '>' . $line, $lines);

        return "*Описание:*\n" . implode("\n", $quotedLines) . "\n\n";
    }

    protected function buildSteamRating(): string
    {
        if (is_null($this->game->steam_rating)) {
            return '';
        }

        $reviewsTitle = $this->steamReviewService->getTitleFromScore($this->game->steam_rating);
        return "⭐ Отзывы: " . $this->markdownHelper->escapeMarkdownV2($reviewsTitle) . "\n";
    }

    protected function buildPrice(User $user): string
    {
        $price = $this->game->prices->where('currency', $user->currency)->first();
        if (!$price) {
            return "💰 Цена: Недоступно в твоём регионе\n";
        }

        $symbol = $this->markdownHelper->escapeMarkdownV2($user->currency->symbol());

        if ($price->discount_percent > 0) {
            $finalPrice = $this->markdownHelper->escapeMarkdownV2($price->final_price);
            $initialPrice = $this->markdownHelper->escapeMarkdownV2($price->initial_price);
            $discount = $this->markdownHelper->escapeMarkdownV2($price->discount_percent);

            $priceText = "*$finalPrice $symbol* ~$initialPrice $symbol~ \\(\\-$discount%\\)";
        } else {
            $finalPrice = $this->markdownHelper->escapeMarkdownV2($price->final_price);
            $priceText = "$finalPrice $symbol";
        }

        return "💰 Цена: $priceText\n";
    }

    protected function buildRatingKeyboard(): Keyboard
    {
        $buttons = array_map(
            fn(UserRatingEnum $rating) => [
                'text' => sprintf('%d %s', $rating->value, $rating->emoji()),
                'callback_data' => sprintf('RateGame:%d,%d', $this->game->id, $rating->value)
            ],
            UserRatingEnum::cases()
        );

        return Keyboard::make([
            'inline_keyboard' => [$buttons]
        ]);
    }
}
