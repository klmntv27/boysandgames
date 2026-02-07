<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TelegramMiniappMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Попробуем получить initData из разных источников
        $initData = $request->input('initData')
            ?? $request->header('X-Telegram-Init-Data')
            ?? $request->query('initData');

        if ($initData) {
            $validated = $this->validateTelegramInitData($initData);

            if ($validated && isset($validated['user'])) {
                $userData = json_decode($validated['user'], true);

                // Find or create user
                $user = User::firstOrCreate(
                    ['telegram_id' => $userData['id']],
                    [
                        'first_name' => $userData['first_name'] ?? null,
                        'last_name' => $userData['last_name'] ?? null,
                        'nickname' => $userData['username'] ?? null,
                    ]
                );

                Auth::login($user);
            }
        }

        return $next($request);
    }

    private function validateTelegramInitData(string $initData): ?array
    {
        $botToken = config('telegram.bots.mybot.token');

        parse_str($initData, $data);

        $checkHash = $data['hash'] ?? '';
        unset($data['hash']);

        ksort($data);

        $dataCheckString = http_build_query($data, '', "\n");
        $dataCheckString = urldecode($dataCheckString);

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        if (strcmp($hash, $checkHash) === 0) {
            return $data;
        }

        return null;
    }
}
