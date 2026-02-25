<?php

namespace App\Jobs;

use App\Enums\CurrencyEnum;
use App\Helpers\MarkdownHelper;
use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Telegram\Bot\Laravel\Facades\Telegram;

class NotifyPriceDroppedJob implements ShouldQueue
{
    use Queueable;

    protected string $message;

    public function __construct(
        protected Game $game,
        protected float $oldPrice,
        protected float $newPrice,
        protected CurrencyEnum $currency,
        protected MarkdownHelper $markdownHelper,
    ) {
        $this->message = sprintf(
            "Скидка на игру [%s](%s)\\!\nРейтинг: %s\nВалюта: %s\n\nЦена вчера: %s %s\nЦена сегодня: %s %s",
            $this->markdownHelper->escapeMarkdownV2($this->game->name),
            $this->markdownHelper->escapeMarkdownV2($this->game->steam_url),
            $this->markdownHelper->escapeMarkdownV2($this->game->average_rating),
            $this->currency->flagEmoji(),
            $this->markdownHelper->escapeMarkdownV2($this->oldPrice),
            $this->currency->symbol(),
            $this->markdownHelper->escapeMarkdownV2($this->newPrice),
            $this->currency->symbol(),
        );
    }

    public function handle(): void
    {
        foreach ($this->getMailingList() as $recipient) {
            Telegram::sendMessage([
                'chat_id' => $recipient->telegram_id,
                'text' => $this->message,
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true,
            ]);
        }
    }

    protected function getMailingList(): Collection
    {
        return User::whereCurrency($this->currency)->get();
    }
}
