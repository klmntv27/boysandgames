<?php

namespace App\Models;

use App\Enums\UserRatingEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRating
 */
class Rating extends Model
{
    public $timestamps = false;

    protected $casts = [
        'rating' => UserRatingEnum::class,
        'rated_at' => 'datetime',
    ];
}
