<?php

namespace App\Http\Controllers;

use App\Helpers\MarkdownHelper;
use App\Http\HasTelegramCallbackData;
use App\Jobs\NotifyAboutNewReplyJob;
use App\Models\User;

class TelegramController extends Controller
{
    use HasTelegramCallbackData;

    public function __invoke()
    {
        if ($this->isCommand()) {
            $this->telegram->commandsHandler(webhook: true);
            return response()->json(['status' => 'command processed']);
        }

        if ($this->isCallback()) {
            return $this->handleCallback();
        }

        if ($this->isReply()) {
            return $this->handleReply();
        }

        return response()->json(['status' => 'unknown update type']);
    }

    protected function isCommand(): bool
    {
        $text = $this->message?->getText();
        return $text && str_starts_with($text, '/');
    }

    protected function isCallback(): bool
    {
        return $this->callbackQuery?->getData() !== null;
    }

    protected function isReply(): bool
    {
        return $this->message?->getReplyToMessage() !== null;
    }

    protected function handleCallback()
    {
        $callbackData = $this->callbackQuery->getData();
        [$callbackClassName, $callbackArgs] = explode(':', $callbackData);
        $callbackClassName = sprintf("App\Http\Callbacks\%sCallback", ucfirst($callbackClassName));

        if (class_exists($callbackClassName)) {
            $callback = new $callbackClassName($this->telegram);
            return $callback->handle(...explode(',', $callbackArgs));
        }

        return response()->json(['status' => 'callback not found', 'callback' => $callbackClassName]);
    }

    protected function handleReply()
    {
        $user = User::where('telegram_id', $this->from->getId())->first();

        if (!$user) {
            return response()->json(['status' => 'user not found']);
        }

        $replyToMessage = $this->message->getReplyToMessage();

        if ($replyToMessage->getFrom()->getId() === $this->from->getId()) {
            $this->send(['text' => 'Вы не можете ответить на своё сообщение']);
            return response()->json(['status' => 'cannot reply to own message']);
        }
        $replyText = $replyToMessage->getCaption() ?: $replyToMessage->getText();

        if (count($lines = explode("\n\n", $replyText)) < 2) {
            $this->send(['text' => 'Вы не можете ответить на такой тип сообщения']);
            return response()->json(['status' => 'reply not found']);
        }

        foreach ($lines as $part) {
            NotifyAboutNewReplyJob::dispatch(
                $user,
                $this->message->getText(),
                $part,
                new MarkdownHelper()
            );

            break;
        }

        return response()->json(['status' => 'reply processed']);
    }
}
