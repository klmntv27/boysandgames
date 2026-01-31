<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\User;
use App\Services\ConvertReviewScoreToTitleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class RequestGameRatingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Game $game
    ) {}

    private function escapeMarkdownV2(string $text): string
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        foreach ($specialChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }

        return $text;
    }

    public function handle(ConvertReviewScoreToTitleService $convertReviewService): void
    {
        $initiator = $this->game->initiator;
        $mailingList = User::query()->where('id', '!=', $initiator->id)->get();

        foreach ($mailingList as $user) {
            $price = $this->game->prices()->where('currency', $user->currency)->first();
            $priceText = $price ? "$price->final_price {$user->currency->symbol()}" : "Цена не указана";

            $steamUrl = "https://store.steampowered.com/app/{$this->game->steam_id}";

            $message = sprintf(
                "%s \\(@%s\\) предлагает поиграть в [%s](%s)\n\n",
                $this->escapeMarkdownV2($initiator->first_name),
                $this->escapeMarkdownV2($initiator->nickname),
                $this->escapeMarkdownV2($this->game->name),
                $steamUrl
            );

            if ($this->game->description) {
                $message .= "*Описание:*\n||" . $this->escapeMarkdownV2($this->game->description) . "||\n\n";
            }

            if ($this->game->steam_rating) {
                $reviewsTitle = $convertReviewService->getTitleFromScore($this->game->steam_rating);

                \Log::debug($reviewsTitle);

                $message .= "⭐ Отзывы: " . $this->escapeMarkdownV2($reviewsTitle) . "\n";
            }

            $message .= "💰 Цена: " . $this->escapeMarkdownV2($priceText) . "\n";

            if ($this->game->trailer_url) {
                $message .= "\n🎬 [Смотреть трейлер]({$this->game->trailer_url})";
            }

            $params = [
                'chat_id' => $user->telegram_id,
                'text' => $message,
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => false,
            ];

            if ($this->game->trailer_thumbnail) {
                $params['photo'] = InputFile::create($this->game->trailer_thumbnail);
                $params['caption'] = $message;
                unset($params['text']);
                Telegram::sendPhoto($params);
            } else {
                Telegram::sendMessage($params);
            }
        }
    }
}
