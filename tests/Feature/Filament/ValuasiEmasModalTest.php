<?php

use App\Models\BukuKas;
use App\Services\HargaEmasService;
use App\Services\TabunganEmasService;
use Illuminate\Support\Carbon;

test('modal valuasi emas memakai konfigurasi sumber dari environment', function () {
    $environment = \Illuminate\Support\Env::getRepository();
    $original = $environment->get('HARGA_EMAS_SOURCE');

    try {
        $environment->set('HARGA_EMAS_SOURCE', 'https://example.com/sumber-emas');
        $services = require config_path('services.php');

        expect($services['harga_emas']['source'])->toBe('https://example.com/sumber-emas');
    } finally {
        if ($original === null) {
            $environment->clear('HARGA_EMAS_SOURCE');
        } else {
            $environment->set('HARGA_EMAS_SOURCE', $original);
        }
    }
});

test('modal valuasi emas merender ringkasan dan indikator hasil', function ($hasil, $trend, $status, $label) {
    config(['services.harga_emas.url' => 'https://logam-mulia-api.iamutaki.workers.dev/api/prices/anekalogam']);
    config(['services.harga_emas.source' => 'https://anekalogam.co.id/id']);
    $kas = new BukuKas;
    $this->mock(HargaEmasService::class)->shouldReceive('hargaBuyback')->once()->with($kas, true)->andReturn([
        'harga_per_gram' => 1200000,
        'provider' => 'Penyedia uji',
        'status' => $status,
        'berlaku_pada' => Carbon::parse('2026-09-13 10:00'),
    ]);
    $this->mock(TabunganEmasService::class)->shouldReceive('valuasi')->once()->with($kas, 1200000)->andReturn([
        'berat_gram' => 2.5,
        'nilai_emas' => 3000000,
        'saldo_rupiah' => 500000,
        'total_nilai_kas' => 3500000,
        'total_modal' => 3000000 - $hasil,
        'untung_rugi' => $hasil,
    ]);

    $html = view('filament.valuasi-emas', ['record' => $kas])->render();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($dom);

    expect($xpath->evaluate('string(//*[@data-value="total"])'))->toBe('Rp 3.500.000')
        ->and($xpath->evaluate('string(//*[@data-value="gold"])'))->toBe('Rp 3.000.000')
        ->and($xpath->evaluate('string(//*[@data-value="cash"])'))->toBe('Rp 500.000')
        ->and($xpath->evaluate('string(//*[@data-trend]/@data-trend)'))->toBe($trend)
        ->and($xpath->evaluate('string(//*[@data-value="result"])'))->toBe(($hasil > 0 ? '+' : ($hasil < 0 ? '−' : '')).'Rp '.number_format(abs($hasil), 0, ',', '.'))
        ->and($xpath->evaluate('normalize-space(//*[@class="emas-badge"])'))->toBe($label)
        ->and($xpath->evaluate('count(//*[@role="alert"])'))->toBe(0.0);

    if ($status === 'manual') {
        expect($xpath->evaluate('count(//a[@class="emas-source"])'))->toBe(0.0);
    } else {
        expect($xpath->evaluate('string(//a[@class="emas-source"]/@href)'))->toBe(config('services.harga_emas.source'))
            ->and($xpath->evaluate('string(//a[@class="emas-source"]/@target)'))->toBe('_blank')
            ->and($xpath->evaluate('string(//a[@class="emas-source"]/@rel)'))->toBe('noopener noreferrer')
            ->and($xpath->evaluate('normalize-space(//a[@class="emas-source"])'))->toBe('Penyedia uji');
    }
})->with([
    'untung dengan harga terbaru' => [500000, 'profit', 'terbaru', 'Harga terbaru'],
    'rugi dengan harga tersimpan' => [-500000, 'loss', 'cache', 'Harga tersimpan'],
    'impas dengan harga manual' => [0, 'neutral', 'manual', 'Harga manual'],
]);

test('modal valuasi emas merender kegagalan harga dengan aman', function () {
    $this->mock(HargaEmasService::class)->shouldReceive('hargaBuyback')->once()
        ->andThrow(new RuntimeException('Harga belum tersedia <script>alert(1)</script>'));
    $this->mock(TabunganEmasService::class)->shouldNotReceive('valuasi');

    $html = view('filament.valuasi-emas', ['record' => new BukuKas])->render();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($dom);

    expect($xpath->evaluate('count(//*[@role="alert"])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//*[@data-value="total"])'))->toBe(0.0)
        ->and($xpath->evaluate('count(//script)'))->toBe(0.0)
        ->and($xpath->evaluate('string(//*[@role="alert"])'))->toContain('Harga belum tersedia');
});
