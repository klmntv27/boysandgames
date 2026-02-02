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

        if (!isset($data[$appId]['success']) || !$data[$appId]['success']) {
            return null;
        }

        $gameData = $data[$appId]['data'];

        return [
            'steam_id' => $appId,
            'name' => $gameData['name'] ?? null,
            'description' => $gameData['short_description'] ?? '',
            'screenshots' => $this->extractScreenshots($gameData),
            'system_requirements' => $this->extractSystemRequirements($gameData),
            'player_categories' => $this->extractPlayerCategories($gameData),
        ];
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

    private function extractSystemRequirements(array $gameData): ?string
    {
        if (empty($gameData['pc_requirements']['minimum'])) {
            return null;
        }

        return strip_tags(
            str_replace('<br>', "\n", $gameData['pc_requirements']['minimum'])
        );
    }

    private function extractPlayerCategories(array $gameData): ?string
    {
        if (empty($gameData['categories'])) {
            return null;
        }

        $categories = array_map(
            fn($category) => $category['description'] ?? '',
            $gameData['categories']
        );

        $categories = array_filter(
            $categories,
            fn($category) => str_contains(strtolower($category), 'player')
                || str_contains(strtolower($category), 'coop')
                || str_contains(strtolower($category), 'игрок')
                || str_contains(strtolower($category), 'кооп')
                || str_contains(strtolower($category), 'плеер')
        );

        return !empty($categories) ? implode(', ', $categories) : null;
    }

    public function extractAppIdFromUrl(string $url): ?int
    {
        if (preg_match('/store\.steampowered\.com\/app\/(\d+)/', $url, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}

