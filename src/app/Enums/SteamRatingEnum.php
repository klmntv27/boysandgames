<?php

namespace App\Enums;

enum SteamRatingEnum: int
{
    case OverwhelminglyPositive = 0;
    case VeryPositive = 1;
    case Positive = 2;
    case MostlyPositive = 3;
    case Mixed = 4;
    case MostlyNegative = 5;
    case Negative = 6;
    case VeryNegative = 7;
    case OverwhelminglyNegative = 8;

    public function title(): string
    {
        return match ($this) {
            self::OverwhelminglyPositive => 'Крайне положительные',
            self::VeryPositive => 'Очень положительные',
            self::Positive => 'Положительные',
            self::MostlyPositive => 'В основном положительные',
            self::Mixed => 'Смешанные',
            self::MostlyNegative => 'В основном отрицательные',
            self::Negative => 'Отрицательные',
            self::VeryNegative => 'Очень отрицательные',
            self::OverwhelminglyNegative => 'Крайне отрицательные',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OverwhelminglyPositive, self::VeryPositive => 'green-700',
            self::Positive, self::MostlyPositive => 'green-500',
            self::Mixed => 'yellow-500',
            self::MostlyNegative, self::Negative => 'red-500',
            self::VeryNegative, self::OverwhelminglyNegative => 'red-700',
        };
    }
}
