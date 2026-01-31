<?php

namespace App\Models;

use App\Models\Attributes\HasGameAttributes;
use App\Models\Relations\HasGameRelations;
use Illuminate\Database\Eloquent\Model;

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
        'steam_rating',
        'trailer_url',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];
}
