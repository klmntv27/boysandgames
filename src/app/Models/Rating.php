<?php

namespace App\Models;

use App\Enums\UserRatingEnum;
use App\Models\Relations\HasRatingRelations;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRating
 */
class Rating extends Model
{
    use HasRatingRelations;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'rating' => UserRatingEnum::class,
        'rated_at' => 'datetime',
    ];
}
