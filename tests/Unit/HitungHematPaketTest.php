<?php

use App\Models\PaketLangganan;
use App\Services\Langganan\HitungHematPaket;

function buatPaketHemat(int $id, string $label, int $harga, int $hari): PaketLangganan
{
    // Model belum disimpan; id diisi manual untuk kebutuhan perhitungan murni.
    $paket = new PaketLangganan(['label' => $label, 'harga' => $harga, 'durasi_hari' => $hari]);
    $paket->setAttribute('id', $id);

    return $paket;
}

test('hitung hemat paket contoh menghasilkan sorotan dan persen yang tepat', function () {
    $paket = collect([
        buatPaketHemat(1, '3 Bulan', 63000, 90),
        buatPaketHemat(2, '6 Bulan', 126000, 180),
        buatPaketHemat(3, '9 Bulan', 169000, 270),
        buatPaketHemat(4, '1 Tahun', 199000, 365),
    ]);

    $hasil = app(HitungHematPaket::class)->handle($paket)->keyBy('id');

    // Paket acuan tanpa hemat dan tanpa sorotan.
    expect($hasil[1]['is_acuan'])->toBeTrue()
        ->and((float) $hasil[1]['hemat_bulan'])->toBe(0.0)
        ->and($hasil[1]['is_terbaik'])->toBeFalse();

    // Paket 6 bulan tanpa hemat; tidak disorot.
    expect((float) $hasil[2]['hemat_bulan'])->toBe(0.0)
        ->and($hasil[2]['persen'])->toBe(0)
        ->and($hasil[2]['is_terbaik'])->toBeFalse();

    // Paket 9 bulan hemat Rp2.222/bulan (10%).
    expect(round($hasil[3]['hemat_bulan']))->toBe(2222.0)
        ->and($hasil[3]['persen'])->toBe(10)
        ->and($hasil[3]['is_terbaik'])->toBeFalse();

    // Paket 1 tahun hemat Rp4.417/bulan (21%) dan disorot paling hemat.
    expect(round($hasil[4]['hemat_bulan']))->toBe(4417.0)
        ->and($hasil[4]['persen'])->toBe(21)
        ->and($hasil[4]['is_terbaik'])->toBeTrue();

    // Urutan durasi terpanjang dulu.
    expect(app(HitungHematPaket::class)->handle($paket)->pluck('id')->all())->toBe([4, 3, 2, 1]);
});

test('hitung hemat menangani kasus tepi daftar kosong tunggal dan harga sama', function () {
    $layanan = app(HitungHematPaket::class);

    expect($layanan->handle(collect()))->toHaveCount(0);

    $tunggal = $layanan->handle(collect([buatPaketHemat(9, '3 Bulan', 63000, 90)]));
    expect($tunggal)->toHaveCount(1)
        ->and($tunggal->first()['is_acuan'])->toBeTrue()
        ->and($tunggal->first()['is_terbaik'])->toBeFalse()
        ->and((float) $tunggal->first()['hemat_bulan'])->toBe(0.0);

    $sama = $layanan->handle(collect([
        buatPaketHemat(11, 'Paket A', 60000, 90),
        buatPaketHemat(12, 'Paket B', 120000, 180),
    ]));
    expect($sama->pluck('is_terbaik')->all())->toBe([false, false])
        ->and($sama->pluck('hemat_bulan')->map(fn ($nilai) => (float) $nilai)->all())->toBe([0.0, 0.0]);
});

test('durasi 365 hari dibulatkan menjadi 12 bulan', function () {
    $hasil = app(HitungHematPaket::class)->handle(collect([
        buatPaketHemat(1, '3 Bulan', 63000, 90),
        buatPaketHemat(4, '1 Tahun', 199000, 365),
    ]))->keyBy('id');

    expect($hasil[4]['bulan'])->toBe(12)
        ->and($hasil[4]['harga_coret'])->toBe(252000.0);
});
