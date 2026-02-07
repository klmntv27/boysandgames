<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Rating;
use App\Enums\UserRatingEnum;
use App\Enums\CurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MiniappController extends Controller
{
    /**
     * Список игр с рейтингом
     */
    public function games(Request $request)
    {
        $user = Auth::user();

        $games = Game::select('games.*')
            ->selectRaw('AVG(ratings.rating) as average_rating')
            ->leftJoin('ratings', 'games.id', '=', 'ratings.game_id')
            ->with(['prices'])
            ->groupBy('games.id')
            ->orderByRaw('AVG(ratings.rating) IS NULL, AVG(ratings.rating) DESC')
            ->get()
            ->map(function ($game, $index) use ($user) {
                $userRating = $game->ratings()
                    ->where('user_id', $user->id)
                    ->first();

                $price = $game->prices->where('currency', $user->currency->value)->first();

                return [
                    'id' => $game->id,
                    'number' => $index + 1,
                    'name' => $game->name,
                    'preview_image_url' => $game->preview_image_url,
                    'user_rating' => $userRating?->rating?->value,
                    'user_rating_title' => $userRating?->rating?->title(),
                    'average_rating' => $game->average_rating ? round($game->average_rating, 2) : null,
                    'price' => $price ? [
                        'initial_price' => $price->initial_price,
                        'final_price' => $price->final_price,
                        'discount_percent' => $price->discount_percent,
                        'currency_symbol' => $user->currency->symbol(),
                    ] : null,
                ];
            });

        return response()->json($games);
    }

    /**
     * Детали игры
     */
    public function gameDetail(Request $request, int $gameId)
    {
        $user = Auth::user();

        $game = Game::with(['images', 'prices'])->findOrFail($gameId);

        $userRating = $game->ratings()
            ->where('user_id', $user->id)
            ->first();

        $averageRating = $game->ratings()->avg('rating');
        $isInitiator = $game->initiator_id === $user->id;

        return response()->json([
            'id' => $game->id,
            'name' => $game->name,
            'description' => $game->description,
            'steam_id' => $game->steam_id,
            'steam_rating' => $game->steam_rating,
            'system_requirements' => $game->system_requirements,
            'player_categories' => $game->player_categories,
            'added_at' => $game->added_at,
            'is_initiator' => $isInitiator,
            'images' => $game->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'type' => $img->type,
            ]),
            'prices' => $game->prices->mapWithKeys(fn($price) => [
                $price->currency => [
                    'initial_price' => $price->initial_price,
                    'final_price' => $price->final_price,
                    'discount_percent' => $price->discount_percent,
                ]
            ]),
            'user_rating' => $userRating?->rating?->value,
            'user_rating_title' => $userRating?->rating?->title(),
            'average_rating' => $averageRating ? round($averageRating, 2) : null,
        ]);
    }

    /**
     * Список оценок игры
     */
    public function gameRatings(Request $request, int $gameId)
    {
        $ratings = Rating::where('game_id', $gameId)
            ->with('user')
            ->orderByDesc('rated_at')
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'rating' => $rating->rating->value,
                    'rating_title' => $rating->rating->title(),
                    'rating_emoji' => $rating->rating->emoji(),
                    'rated_at' => $rating->rated_at->format('d.m.Y H:i'),
                    'user' => [
                        'id' => $rating->user->id,
                        'name' => trim($rating->user->first_name . ' ' . ($rating->user->last_name ?? '')),
                        'nickname' => $rating->user->nickname,
                    ],
                ];
            });

        return response()->json($ratings);
    }

    /**
     * Изменение оценки игры
     */
    public function rateGame(Request $request, int $gameId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = Auth::user();
        $game = Game::findOrFail($gameId);

        // Проверка: инициатор не может оценивать свою игру
        if ($game->initiator_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не можете оценивать игру, которую добавили сами',
            ], 403);
        }

        $rating = Rating::updateOrCreate(
            [
                'game_id' => $game->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validated['rating'],
                'rated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'rating' => $rating->rating->value,
            'rating_title' => $rating->rating->title(),
        ]);
    }

    /**
     * Смена валюты пользователя
     */
    public function updateCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|integer|min:1|max:4',
        ]);

        $user = Auth::user();
        $user->currency = CurrencyEnum::from($validated['currency']);
        $user->save();

        return response()->json([
            'success' => true,
            'currency' => $user->currency->value,
            'currency_title' => $user->currency->title(),
            'currency_flag' => $user->currency->flagEmoji(),
        ]);
    }
}
