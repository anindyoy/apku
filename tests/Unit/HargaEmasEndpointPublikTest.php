<?php

test('harga_emas_endpoint_publik_stabil_dan_memuat_buyback_valid', function () {
    if (getenv('RUN_EXTERNAL_GOLD_API_TEST') !== '1') {
        $this->markTestSkipped('Smoke test endpoint eksternal hanya dijalankan oleh monitor terjadwal.');
    }

    $url = getenv('HARGA_EMAS_URL') ?: 'https://logam-mulia-api.iamutaki.workers.dev/api/prices/anekalogam';
    $mulai = microtime(true);
    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'APKu-Endpoint-Monitor/1.0',
    ]);

    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $tipeKonten = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $error = curl_error($curl);
    curl_close($curl);

    $durasi = microtime(true) - $mulai;

    expect($body)->not->toBeFalse("Endpoint gagal diakses: {$error}")
        ->and($status)->toBe(200, "Endpoint mengembalikan HTTP {$status}")
        ->and($tipeKonten)->toContain('application/json')
        ->and($durasi)->toBeLessThan(15.0, 'Respons endpoint melebihi 15 detik.');

    $respons = json_decode((string) $body, true, flags: JSON_THROW_ON_ERROR);
    $hargaValid = collect($respons['data'] ?? [])->filter(fn ($item): bool => is_array($item)
        && ($item['material'] ?? null) === 'gold'
        && ($item['currency'] ?? null) === 'IDR'
        && (float) ($item['weight'] ?? 0) > 0
        && (int) ($item['buybackPrice'] ?? 0) > 0);

    expect($respons['success'] ?? false)->toBeTrue()
        ->and($hargaValid)->not->toBeEmpty('Respons tidak memiliki harga buyback emas IDR yang valid.');

    $tanggalTerbaru = $hargaValid
        ->pluck('recordedDate')
        ->filter()
        ->map(fn (string $tanggal): int => strtotime($tanggal) ?: 0)
        ->max();

    expect($tanggalTerbaru)->toBeGreaterThanOrEqual(
        strtotime('-7 days'),
        'Harga buyback terakhir berusia lebih dari tujuh hari.',
    );
})->group('external', 'harga-emas-endpoint');
