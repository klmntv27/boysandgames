<?php

namespace App\Models;

use App\Enums\SteamRatingEnum;
use App\Models\Attributes\HasGameAttributes;
use App\Models\Relations\HasGameRelations;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasGameRelations,
        HasGameAttributes;

    public $timestamps = false;

    protected $casts = [
        'steam_rating' => SteamRatingEnum::class,
        'added_at' => 'datetime',
    ];
}
