<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamApiReviewsService
{
    private const REVIEWS_URL = 'https://store.steampowered.com/appreviews';

    public function getGameRating(int $appId): ?int
    {
        $response = Http::get(self::REVIEWS_URL . '/' . $appId, [
            'json' => 1,
            'language' => 'all',
            'purchase_type' => 'all',
            'num_per_page' => 0,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['success']) || $data['success'] !== 1) {
            return null;
        }

        $queryData = $data['query_summary'] ?? null;

        if (!$queryData) {
            return null;
        }

        $totalReviews = $queryData['total_reviews'] ?? 0;

        if ($totalReviews === 0) {
            return null;
        }

        $positiveReviews = $queryData['total_positive'] ?? 0;

        return (int) round(($positiveReviews / $totalReviews) * 100);
    }
}
