<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $timestamps = false;

    protected $casts = [
        'currency' => CurrencyEnum::class,
    ];
}
