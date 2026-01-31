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
            'screenshots' => $this->extractScreenshots($gameData),
        ];
    }

    private function extractRating(array $gameData): ?string
    {
        return $gameData['reviews'] ?? 'No data';
    }

    private function extractScreenshots(array $gameData): array
    {
        if (empty($gameData['screenshots'])) {
            return [];
        }

        return array_slice(
            array_map(
                fn($screenshot) => $screenshot['path_full'],
                $gameData['screenshots']
            ),
            0,
            10
        );
    }

    public function extractAppIdFromUrl(string $url): ?int
    {
        if (preg_match('/store\.steampowered\.com\/app\/(\d+)/', $url, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

