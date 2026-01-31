<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Models\Relations\HasUserRelations;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    use HasUserRelations;

    public $timestamps = false;

    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'nickname',
        'currency',
    ];

    protected $casts = [
        'currency' => CurrencyEnum::class,
    ];
}
