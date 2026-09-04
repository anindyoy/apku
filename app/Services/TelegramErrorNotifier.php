<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramErrorNotifier
{
    public function send(Throwable $exception): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! app()->environment('production') || blank($token) || blank($chatId)) {
            return;
        }

        $user = Auth::user();
        $message = implode("\n", [
            'Error: '.$exception->getMessage(),
            'File: '.$exception->getFile(),
            'Line: '.$exception->getLine(),
            'Domain: '.config('app.url'),
            '',
            'Username: '.($user?->name ?? '-'),
            'Email: '.($user?->email ?? '-'),
            '',
            'Versi: #APKu',
        ]);

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id' => (string) $chatId,
                    'text' => $message,
                ],
            );

            if ($response->failed()) {
                Log::error('Pengiriman notifikasi error ke Telegram gagal.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (Throwable $telegramException) {
            Log::error('Pengiriman notifikasi error ke Telegram mengalami exception.', [
                'message' => $telegramException->getMessage(),
            ]);
        }
    }
}
