<?php

namespace App\Helpers;

class MarkdownHelper
{
    protected const SPECIAL_CHARS = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

    public function escapeMarkdownV2(string $text): string
    {
        foreach (self::SPECIAL_CHARS as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }

        return $text;
    }
}
