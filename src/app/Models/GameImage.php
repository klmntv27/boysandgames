<?php

namespace App\Models;

use App\Models\Relations\HasGameImageRelations;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperGameImage
 */
class GameImage extends Model
{
    use HasGameImageRelations;

    public $timestamps = false;

    protected $fillable = [
        'game_id',
        'url',
    ];
}
