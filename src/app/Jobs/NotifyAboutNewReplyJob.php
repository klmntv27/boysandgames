<?php

namespace App\Jobs;

use App\Helpers\MarkdownHelper;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;

class NotifyAboutNewReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $user,
        protected string $message,
        protected string $quote,
        protected MarkdownHelper $markdownHelper
    ) {}

    public function handle(): void
    {
        $users = User::all()->except($this->user->id);

        foreach ($users as $user) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => $this->buildMessage(),
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true,
            ]);
        }
    }

    protected function buildMessage(): string
    {
        $escapedQuote = $this->markdownHelper->escapeMarkdownV2($this->quote);
        $escapedName = $this->markdownHelper->escapeMarkdownV2($this->user->first_name);
        $escapedNickname = $this->markdownHelper->escapeMarkdownV2($this->user->nickname);
        $quotedLines = $this->markdownHelper->escapeMarkdownV2($this->message);

        return sprintf(
            "🗣 %s \(@%s\) говорит:\n`%s`\n\nВ ответ на:\n`%s`",
            $escapedName,
            $escapedNickname,
            $quotedLines,
            $escapedQuote,
        );
    }
}
