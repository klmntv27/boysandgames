<?php

namespace App\Models\Relations;

use App\Models\File;
use App\Models\GamePrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasGameRelations
{
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(GamePrice::class);
    }
}
