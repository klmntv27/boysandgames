<?php

namespace App\Enums;

enum CurrencyEnum: int
{
    case RUB = 1;
    case USD = 2;
    case EUR = 3;
    case KZT = 4;

    public function title(): string
    {
        return match ($this) {
            CurrencyEnum::RUB => 'RUB',
            CurrencyEnum::USD => 'USD',
            CurrencyEnum::EUR => 'EUR',
            CurrencyEnum::KZT => 'KZT',
        };
    }

    public function flagEmoji(): string
    {
        return match ($this) {
            CurrencyEnum::RUB => '🇷🇺',
            CurrencyEnum::USD => '🇺🇸',
            CurrencyEnum::EUR => '🇪🇺',
            CurrencyEnum::KZT => '🇰🇿',
        };
    }
}
