<?php

namespace App\Services;

class ConvertReviewScoreToTitleService
{
    public function getTitleFromScore(int $score): string
    {
        return match (true) {
            $score >= 95 => 'Крайне положительные 🔥',
            $score >= 80 => 'Очень положительные 👍',
            $score >= 70 => 'В основном положительные 🙂',
            $score >= 40 => 'Смешанные 😐',
            $score >= 20 => 'В основном отрицательные 👎',
            default => 'Крайне отрицательные 💀',
        };
    }
}
