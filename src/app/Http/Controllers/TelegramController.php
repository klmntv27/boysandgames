<?php

namespace App\Http\Controllers;

use App\Http\HasTelegramCallbackData;

class TelegramController extends Controller
{
    use HasTelegramCallbackData;

    public function __invoke()
    {
        $callbackName = $this->update->getCallbackQuery()->getData();

        [$callbackClassName, $callbackArgs] = explode(':', $callbackName);
        $callbackClassName = sprintf("App\Http\Callbacks\%sCallback", ucfirst($callbackClassName));

        if(class_exists($callbackClassName)) {
            $callback = new $callbackClassName($this->telegram);
            return $callback->handle(...explode(',', $callbackArgs));
        }

        return response()->json([$callbackClassName, $callbackArgs]);
    }
}
