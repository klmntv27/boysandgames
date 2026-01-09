<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Models\Relations\HasUserRelations;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUserRelations;

    public $timestamps = false;

    protected $casts = [
        'currency' => CurrencyEnum::class,
    ];
}
