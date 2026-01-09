<?php

namespace App\Http\Middleware;

use App\Enums\CurrencyEnum;
use App\Http\HasTelegramCallbackData;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Telegram\Bot\Keyboard\Keyboard;

class EnsureUserIsRegisteredMiddleware
{
    use HasTelegramCallbackData;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isCurrencySelectingCallback()) {
            return $next($request);
        }

        if (!User::where('telegram_id', $this->from->getId())->exists()) {
            $this->telegram->sendMessage([
                'chat_id'      => $this->chatId,
                'text'         => "Добро пожаловать в бот! Для продолжения выберите валюту. Все цены будут указаны в ней.",
                'reply_markup' => Keyboard::make([
                    'inline_keyboard' => array_map(
                        fn(CurrencyEnum $currency) => [[
                            'text'          => sprintf('%s %s', $currency->flagEmoji(), $currency->title()),
                            'callback_data' => sprintf('SetCurrency:%s', $currency->value)
                        ]],
                        CurrencyEnum::cases()
                    )
                ]),
            ]);

            return response()->json(['status' => 'registration required']);
        }

        return $next($request);
    }

    private function isCurrencySelectingCallback(): bool
    {
        return $this->callbackQuery && str_starts_with($this->callbackQuery->getData(), 'SetCurrency:');
    }
}
