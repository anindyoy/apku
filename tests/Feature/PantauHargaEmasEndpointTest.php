<?php

use App\Services\TelegramErrorNotifier;
use Illuminate\Support\Facades\Http;

test('command pantau harga emas menerima respons buyback terbaru', function () {
    config(['services.harga_emas.url' => 'https://example.com/api/emas']);
    $timeouts = [];
    Http::fake(function ($request, $options) use (&$timeouts) {
        $timeouts[] = $options['timeout'];

        return Http::response([
            'success' => true,
            'data' => [[
                'material' => 'gold',
                'currency' => 'IDR',
                'weight' => 1,
                'buybackPrice' => 1200000,
                'recordedDate' => now()->toDateString(),
            ]],
        ], 200, ['Content-Type' => 'application/json']);
    });

    $this->artisan('harga-emas:pantau')->assertExitCode(0);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/api/emas'
        && $request->hasHeader('User-Agent', 'APKu-Endpoint-Monitor/1.0'));
    expect($timeouts)->toBe([(int) config('services.harga_emas.timeout')]);
});

test('command pantau harga emas menolak buyback yang kedaluwarsa', function () {
    config(['services.harga_emas.url' => 'https://example.com/api/emas']);
    $notifier = $this->mock(TelegramErrorNotifier::class);
    $notifier->shouldReceive('send')->once()->with(\Mockery::type(RuntimeException::class));
    Http::fake([
        'https://example.com/api/emas' => Http::response([
            'success' => true,
            'data' => [[
                'material' => 'gold',
                'currency' => 'IDR',
                'weight' => 1,
                'buybackPrice' => 1200000,
                'recordedDate' => now()->subDays(8)->toDateString(),
            ]],
        ], 200, ['Content-Type' => 'application/json']),
    ]);

    $this->artisan('harga-emas:pantau')->assertExitCode(1);
});