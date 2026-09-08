<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TurnstileToken implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret_key');

        if (blank($secretKey)) {
            $fail('Verifikasi keamanan belum dikonfigurasi.');

            return;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('services.turnstile.timeout', 5))
                ->post(config('services.turnstile.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (ConnectionException) {
            $fail('Verifikasi keamanan sedang tidak tersedia. Silakan coba lagi.');

            return;
        }

        $hostname = $response->json('hostname');
        $action = $response->json('action');
        $hostnames = config('services.turnstile.hostnames', []);

        if (
            ! $response->successful()
            || $response->json('success') !== true
            || ! is_string($hostname)
            || ! in_array($hostname, $hostnames, true)
            || ! is_string($action)
            || ! hash_equals((string) config('services.turnstile.action'), $action)
        ) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
