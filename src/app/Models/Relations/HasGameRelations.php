<?php

namespace App\Models\Relations;

use App\Models\GameImage;
use App\Models\Price;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasGameRelations
{
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GameImage::class);
    }
}
