<?php

namespace App\Http;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

trait HasTelegramCallbackData
{
    protected Api $telegram;
    protected Update $update;
    protected $message;
    protected $callbackQuery;
    protected $chatId;
    protected $from;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
        $this->update = $this->telegram->getWebhookUpdate();
        $this->message = $this->update->getMessage();
        $this->callbackQuery = $this->update->getCallbackQuery();

        if ($this->callbackQuery) {
            $this->chatId = $this->callbackQuery->getMessage()->getChat()->getId();
            $this->from = $this->callbackQuery->getFrom();
        } elseif ($this->message) {
            $this->chatId = $this->message->getChat()->getId();
            $this->from = $this->message->getFrom();
        }
    }

    protected function send(array $args): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->chatId,
            ...$args
        ]);
    }
}
