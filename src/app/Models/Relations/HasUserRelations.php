<?php

namespace App\Models\Relations;

use App\Models\Rating;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasUserRelations
{
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
