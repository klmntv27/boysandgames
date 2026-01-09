<?php

namespace App\Http\Callbacks;

use App\Enums\CurrencyEnum;
use App\Models\User;

class SetCurrencyCallback extends Callback
{
    public function handle(...$args)
    {
        $currency = CurrencyEnum::from($args[0]);

        if(User::where('telegram_id', $this->from->getId())->exists()) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $this->callbackQuery->getId(),
                'text'              => 'Вы уже зарегистрированы!',
            ]);

            return;
        }

        User::create([
            'telegram_id' => $this->from->getId(),
            'first_name'  => $this->from->getFirstName(),
            'last_name'   => $this->from->getLastName(),
            'nickname'    => $this->from->getUsername(),
            'currency'    => CurrencyEnum::from($args[0]),
        ]);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $this->callbackQuery->getId(),
            'text'              => 'Валюта выбрана: ' . $currency->flagEmoji() . ' ' . $currency->title(),
        ]);

        $this->telegram->editMessageText([
            'chat_id'    => $this->chatId,
            'message_id' => $this->callbackQuery->getMessage()->getMessageId(),
            'text'       => "✅ Регистрация успешно завершена!\n\nВыбранная валюта: {$currency->flagEmoji()} {$currency->title()}\n\nТеперь вы можете пользоваться ботом!",
        ]);
    }
}
