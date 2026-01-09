<?php

namespace App\Http\Callbacks;

use App\Http\HasTelegramCallbackData;

abstract class Callback
{
    use HasTelegramCallbackData;

    abstract public function handle(...$args);
}
