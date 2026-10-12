<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramErrorNotifier
{
    public function send(Throwable $exception): void
    {
        // Hanya aktif di production
        if (app()->environment('production') !== true) {
            return;
        }

        // Baca konfigurasi Telegram langsung
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        // Hanya jika kredensial tersedia (chat_id default untuk exception)
        if (blank($botToken) || blank($chatId)) {
            return;
        }

        // Bangun pesan exception
        $user = null;
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
        } catch (\Throwable) {
            // Jika tidak ada user, lanjutkan tanpa data user
        }

        $messageLines = [
            'Error: ' . $exception->getMessage(),
            'File: ' . $exception->getFile(),
            'Line: ' . $exception->getLine(),
            'Domain: ' . config('app.url'),
        ];

        if ($user) {
            $messageLines[] = 'Username: ' . $user->name;
            $messageLines[] = 'Email: ' . $user->email;
        }

        $messageLines[] = 'Versi: #APKu';

        $message = implode("\n", $messageLines);

        // Potong pesan jika melebihi batas 4096 karakter Telegram
        $maxLength = 4096;
        if (strlen($message) > $maxLength) {
            $message = substr($message, 0, $maxLength - 3) . '...';
        }

        // Kirim via HTTP client Laravel
        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
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
        } catch (\Throwable $telegramException) {
            Log::error('Pengiriman notifikasi error ke Telegram mengalami exception.', [
                'message' => $telegramException->getMessage(),
            ]);
        }
    }
}
