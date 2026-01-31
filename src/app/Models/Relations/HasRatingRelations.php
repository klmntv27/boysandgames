<?php

namespace App\Models\Relations;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRatingRelations
{
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
