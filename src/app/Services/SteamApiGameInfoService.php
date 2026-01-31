<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamApiGameInfoService
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

        \Log::debug($data);

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
            'trailer_thumbnail' => $this->extractTrailerThumbnail($gameData),
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

        // Новый формат (DASH/HLS)
        return $movie['dash_h264']
            ?? $movie['hls_h264']
            ?? $movie['dash_av1']
            // Старый формат (если вернется)
            ?? $movie['webm']['480']
            ?? $movie['webm']['max']
            ?? null;
    }

    private function extractTrailerThumbnail(array $gameData): ?string
    {
        if (empty($gameData['movies'])) {
            return null;
        }

        return $gameData['movies'][0]['thumbnail'] ?? null;
    }

    public function extractAppIdFromUrl(string $url): ?int
    {
        if (preg_match('/store\.steampowered\.com\/app\/(\d+)/', $url, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

