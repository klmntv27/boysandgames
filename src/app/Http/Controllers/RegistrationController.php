<?php

namespace App\Http\Controllers;

use App\Enums\CurrencyEnum;
use App\Models\User;
use Telegram\Bot\Api;

class RegistrationController extends Controller
{
    protected Api $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handleCallbackQuery()
    {
        $update = $this->telegram->getWebhookUpdate();
        $callbackQuery = $update->getCallbackQuery();

        if (!$callbackQuery) {
            return response()->json(['status' => 'no callback query'], 400);
        }

        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $from = $callbackQuery->getFrom();
        $callbackData = $callbackQuery->getData();

        // Проверяем, что это callback для выбора валюты
        if (!str_starts_with($callbackData, 'currency-')) {
            return response()->json(['status' => 'invalid callback'], 400);
        }

        // Извлекаем ID валюты из callback_data
        $currencyId = (int) str_replace('currency_', '', $callbackData);
        $currency = CurrencyEnum::tryFrom($currencyId);

        if (!$currency) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => 'Неверная валюта',
                'show_alert' => true,
            ]);
            return response()->json(['status' => 'invalid currency'], 400);
        }

        // Проверяем, существует ли уже пользователь
        $existingUser = User::where('telegram_id', $from->getId())->first();

        if ($existingUser) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => 'Вы уже зарегистрированы!',
            ]);

            $this->telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $callbackQuery->getMessage()->getMessageId(),
                'text' => 'Вы уже зарегистрированы!',
            ]);

            return response()->json(['status' => 'already registered']);
        }

        // Создаем нового пользователя
        $user = new User();
        $user->telegram_id = $from->getId();
        $user->first_name = $from->getFirstName();
        $user->last_name = $from->getLastName();
        $user->nickname = $from->getUsername();
        $user->currency = $currency;
        $user->save();

        // Отвечаем на callback query
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text' => 'Валюта выбрана: ' . $currency->flagEmoji() . ' ' . $currency->title(),
        ]);

        // Редактируем сообщение, убираем клавиатуру и отправляем подтверждение
        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery->getMessage()->getMessageId(),
            'text' => "✅ Регистрация успешно завершена!\n\nВыбранная валюта: {$currency->flagEmoji()} {$currency->title()}\n\nТеперь вы можете пользоваться ботом!",
        ]);

        return response()->json(['status' => 'registered']);
    }
}


