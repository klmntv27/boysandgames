<?php

namespace App\Http\Controllers;

use App\Http\HasTelegramCallbackData;

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
}
