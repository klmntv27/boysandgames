<?php

namespace App\Models;

use App\Models\Attributes\HasGameAttributes;
use App\Models\Relations\HasGameRelations;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperGame
 */
class Game extends Model
{
    use HasGameRelations,
        HasGameAttributes;

    public $timestamps = false;

    protected $fillable = [
        'initiator_id',
        'steam_id',
        'name',
        'description',
        'preview_image_url',
        'steam_rating',
        'system_requirements',
        'player_categories',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];
}
