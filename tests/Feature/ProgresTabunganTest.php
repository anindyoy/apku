<?php

use App\Models\BukuKas;
use App\Models\User;
use App\Support\ProgresTabungan;
use Illuminate\Support\Carbon;

// Memastikan perhitungan persentase berjalan tepat sesuai spesifikasi (0%, proporsional, 100%, >100%, negatif).
test('perhitungan persentase progres tabungan', function () {
    $user = User::factory()->create([
        'masa_aktif' => now()->addDays(30),
    ]);

    // Kas tabungan dengan saldo 0
    $kasNol = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 0,
        'goal' => 1_000_000,
    ]);
    $kasNol->setRelation('user', $user);
    $progresNol = ProgresTabungan::dari($kasNol, today(), true);
    expect($progresNol->persen)->toBe(0)
        ->and($progresNol->barPersen)->toBe(0);

    // Kas tabungan nilai tengah dibulatkan ke bawah (misal 99,6% menjadi 99%)
    $kasTengah = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 996,
        'goal' => 1000,
    ]);
    $kasTengah->setRelation('user', $user);
    $progresTengah = ProgresTabungan::dari($kasTengah, today(), true);
    expect($progresTengah->persen)->toBe(99)
        ->and($progresTengah->barPersen)->toBe(99);

    // Kas tabungan tepat 100%
    $kasPas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 1_000_000,
        'goal' => 1_000_000,
    ]);
    $kasPas->setRelation('user', $user);
    $progresPas = ProgresTabungan::dari($kasPas, today(), true);
    expect($progresPas->persen)->toBe(100)
        ->and($progresPas->barPersen)->toBe(100);

    // Kas tabungan di atas 100% (bar visual dibatasi 100%)
    $kasLebih = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 1_250_000,
        'goal' => 1_000_000,
    ]);
    $kasLebih->setRelation('user', $user);
    $progresLebih = ProgresTabungan::dari($kasLebih, today(), true);
    expect($progresLebih->persen)->toBe(125)
        ->and($progresLebih->barPersen)->toBe(100);

    // Kas tabungan dengan saldo negatif
    $kasMinus = new BukuKas([
        'is_tabungan' => true,
        'saldo' => -100_000,
        'goal' => 1_000_000,
    ]);
    $kasMinus->setRelation('user', $user);
    $progresMinus = ProgresTabungan::dari($kasMinus, today(), true);
    expect($progresMinus->persen)->toBe(0)
        ->and($progresMinus->barPersen)->toBe(0);
});

// Memastikan goal bernilai null menyembunyikan progres persentase.
test('goal null menyembunyikan progres', function () {
    $kas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 500_000,
        'goal' => null,
    ]);
    $progres = ProgresTabungan::dari($kas, today(), true);
    expect($progres->persen)->toBeNull()
        ->and($progres->barPersen)->toBeNull();
});

// Memastikan batas 1 bulan kalender termasuk akhir bulan Januari dievaluasi dengan benar.
test('batas 1 bulan kalender termasuk akhir bulan', function () {
    $hariIni = Carbon::create(2026, 1, 31, 0, 0, 0);

    // 27 Februari 2026 (kurang dari 1 bulan kalender) -> mendekati
    $kasMendekati = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 100_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-02-27',
    ]);
    $progresMendekati = ProgresTabungan::dari($kasMendekati, $hariIni, true);
    expect($progresMendekati->status)->toBe(ProgresTabungan::STATUS_MENDEKATI);

    // 28 Februari 2026 (tepat 1 bulan kalender berdasarkan addMonthNoOverflow dari 31 Januari) -> berjalan
    $kasPasSatuBulan = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 100_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-02-28',
    ]);
    $progresPasSatuBulan = ProgresTabungan::dari($kasPasSatuBulan, $hariIni, true);
    expect($progresPasSatuBulan->status)->toBe(ProgresTabungan::STATUS_BERJALAN);
});

// Memastikan status deadline hari ini menghasilkan informasi berakhir hari ini.
test('deadline hari ini', function () {
    $hariIni = Carbon::create(2026, 5, 10, 0, 0, 0);
    $kas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 100_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-05-10',
    ]);

    $progres = ProgresTabungan::dari($kas, $hariIni, true);
    expect($progres->status)->toBe(ProgresTabungan::STATUS_MENDEKATI)
        ->and($progres->sisaHari)->toBe(0)
        ->and($progres->labelStatus)->toBe('Berakhir hari ini');
});

// Memastikan deadline sebelum hari ini ditandai sebagai terlewat.
test('status terlewat jika deadline telah lewat dan belum tercapai', function () {
    $hariIni = Carbon::create(2026, 5, 10, 0, 0, 0);
    $kas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 100_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-05-09',
    ]);

    $progres = ProgresTabungan::dari($kas, $hariIni, true);
    expect($progres->status)->toBe(ProgresTabungan::STATUS_TERLEWAT)
        ->and($progres->sisaHari)->toBeLessThan(0);
});

// Memastikan status tercapai menimpa peringatan deadline terlewat.
test('status tercapai menimpa peringatan deadline terlewat', function () {
    $hariIni = Carbon::create(2026, 5, 10, 0, 0, 0);
    $kas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 1_500_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-05-01',
    ]);

    $progres = ProgresTabungan::dari($kas, $hariIni, true);
    expect($progres->status)->toBe(ProgresTabungan::STATUS_TERCAPAI)
        ->and($progres->labelStatus)->toBe('Tercapai');
});

// Memastikan deadline tanpa goal tetap menghasilkan status terlewat atau mendekati tanpa progres persentase.
test('deadline tanpa goal', function () {
    $hariIni = Carbon::create(2026, 5, 10, 0, 0, 0);

    $kasTerlewat = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 200_000,
        'goal' => null,
        'tanggal_goal' => '2026-05-05',
    ]);
    $progresTerlewat = ProgresTabungan::dari($kasTerlewat, $hariIni, true);
    expect($progresTerlewat->persen)->toBeNull()
        ->and($progresTerlewat->status)->toBe(ProgresTabungan::STATUS_TERLEWAT);

    $kasMendekati = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 200_000,
        'goal' => null,
        'tanggal_goal' => '2026-05-15',
    ]);
    $progresMendekati = ProgresTabungan::dari($kasMendekati, $hariIni, true);
    expect($progresMendekati->persen)->toBeNull()
        ->and($progresMendekati->status)->toBe(ProgresTabungan::STATUS_MENDEKATI)
        ->and($progresMendekati->sisaHari)->toBe(5)
        ->and($progresMendekati->labelStatus)->toBe('5 hari lagi');
});

// Memastikan jika premium kedaluwarsa, progres dan status tidak aktif.
test('premium kedaluwarsa memperlakukan tabungan seperti kas biasa', function () {
    $kas = new BukuKas([
        'is_tabungan' => true,
        'saldo' => 500_000,
        'goal' => 1_000_000,
        'tanggal_goal' => '2026-05-15',
    ]);

    $progres = ProgresTabungan::dari($kas, today(), false);
    expect($progres->aktif)->toBeFalse()
        ->and($progres->persen)->toBeNull()
        ->and($progres->barPersen)->toBeNull()
        ->and($progres->status)->toBeNull()
        ->and($progres->labelStatus)->toBeNull();
});
