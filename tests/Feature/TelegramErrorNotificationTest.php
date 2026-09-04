<?php

use Illuminate\Support\Facades\Http;

test('exception production dikirim ke telegram', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    app()->detectEnvironment(fn () => 'production');
    config()->set('app.url', 'https://apku.test');
    config()->set('services.telegram.bot_token', 'token-test');
    config()->set('services.telegram.chat_id', '-100123');

    report(new RuntimeException('Kesalahan pengujian'));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.telegram.org/bottoken-test/sendMessage'
            && $request['chat_id'] === '-100123'
            && str_contains($request['text'], 'Error: Kesalahan pengujian')
            && str_contains($request['text'], 'Domain: https://apku.test')
            && str_contains($request['text'], 'Versi: #APKu');
    });
});

test('exception selain production tidak dikirim ke telegram', function () {
    Http::fake();

    config()->set('services.telegram.bot_token', 'token-test');
    config()->set('services.telegram.chat_id', '-100123');

    report(new RuntimeException('Kesalahan lokal'));

    Http::assertNothingSent();
});
