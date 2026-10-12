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
        buatPaketHemat(0, '1 Bulan', 20000, 30),
        buatPaketHemat(1, '3 Bulan', 57000, 90),
        buatPaketHemat(2, '6 Bulan', 108000, 180),
        buatPaketHemat(3, '9 Bulan', 153000, 270),
        buatPaketHemat(4, '1 Tahun', 192000, 365),
    ]);

    $hasil = app(HitungHematPaket::class)->handle($paket)->keyBy('id');

    // Paket acuan tanpa hemat dan tanpa sorotan.
    expect($hasil[0]['is_acuan'])->toBeTrue()
        ->and((float) $hasil[0]['hemat_bulan'])->toBe(0.0)
        ->and($hasil[0]['is_terbaik'])->toBeFalse();

    // Paket 3 bulan hemat Rp1.000/bulan (5%); tidak disorot karena bukan yang terbesar.
    expect(round($hasil[1]['hemat_bulan']))->toBe(1000.0)
        ->and($hasil[1]['persen'])->toBe(5)
        ->and($hasil[1]['is_terbaik'])->toBeFalse();

    // Paket 6 bulan hemat Rp2.000/bulan (10%).
    expect(round($hasil[2]['hemat_bulan']))->toBe(2000.0)
        ->and($hasil[2]['persen'])->toBe(10)
        ->and($hasil[2]['is_terbaik'])->toBeFalse();

    // Paket 9 bulan hemat Rp3.000/bulan (15%).
    expect(round($hasil[3]['hemat_bulan']))->toBe(3000.0)
        ->and($hasil[3]['persen'])->toBe(15)
        ->and($hasil[3]['is_terbaik'])->toBeFalse();

    // Paket 1 tahun hemat Rp4.000/bulan (20%) dan disorot paling hemat.
    expect(round($hasil[4]['hemat_bulan']))->toBe(4000.0)
        ->and($hasil[4]['persen'])->toBe(20)
        ->and($hasil[4]['is_terbaik'])->toBeTrue();

    // Urutan durasi terpanjang dulu.
    expect(app(HitungHematPaket::class)->handle($paket)->pluck('id')->all())->toBe([4, 3, 2, 1, 0]);
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
        buatPaketHemat(0, '1 Bulan', 20000, 30),
        buatPaketHemat(4, '1 Tahun', 192000, 365),
    ]))->keyBy('id');

    expect($hasil[4]['bulan'])->toBe(12)
        ->and($hasil[4]['harga_coret'])->toBe(240000.0);
});
