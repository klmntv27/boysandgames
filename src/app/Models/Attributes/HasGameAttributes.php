<?php

namespace App\Models\Attributes;

trait HasGameAttributes
{
    public function getAverageRatingAttribute(): ?float
    {
        if ($this->ratings()->count() === 0) {
            return null;
        }

        return round($this->ratings()->avg('rating'), 2);
    }

    public function getSteamUrlAttribute(): string
    {
        return "https://store.steampowered.com/app/$this->steam_id";
    }
}
