<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamApiService
{
    private const BASE_URL = 'https://store.steampowered.com/api/appdetails';

    public function getGameDetails(int $appId): ?array
    {
        $response = Http::get(self::BASE_URL, [
            'appids' => $appId,
            'l' => 'russian',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data[$appId]['success']) || !$data[$appId]['success']) {
            return null;
        }

        $gameData = $data[$appId]['data'];

        return [
            'steam_id' => $appId,
            'name' => $gameData['name'] ?? null,
            'description' => $gameData['short_description'] ?? '',
            'steam_rating' => $this->extractRating($gameData),
            'trailer_url' => $this->extractTrailerUrl($gameData),
        ];
    }

    private function extractRating(array $gameData): ?string
    {
        return $gameData['reviews'] ?? 'No data';
    }

    private function extractTrailerUrl(array $gameData): ?string
    {
        if (empty($gameData['movies'])) {
            return null;
        }

        $movie = $gameData['movies'][0];

        return $movie['webm']['480'] ?? $movie['webm']['max'] ?? null;
    }

    public function extractAppIdFromUrl(string $url): ?int
    {
        if (preg_match('/store\.steampowered\.com\/app\/(\d+)/', $url, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

