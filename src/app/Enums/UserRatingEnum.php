<?php

namespace App\Enums;

enum UserRatingEnum: int
{
    case AbsolutelyNot = 1;
    case MoreNoThanYes = 2;
    case IDontKnow = 3;
    case MoreYesThanNo = 4;
    case AbsolutelyYes = 5;

    public function title(): string
    {
        return match ($this) {
            self::AbsolutelyNot => 'Точно нет',
            self::MoreNoThanYes => 'Скорее нет, чем да',
            self::IDontKnow => 'Не знаю',
            self::MoreYesThanNo => 'Скорее да, чем нет',
            self::AbsolutelyYes => 'Точно да',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AbsolutelyNot => 'red',
            self::MoreNoThanYes => 'orange',
            self::IDontKnow => 'gray',
            self::MoreYesThanNo => 'light-green',
            self::AbsolutelyYes => 'green',
        };
    }
}
