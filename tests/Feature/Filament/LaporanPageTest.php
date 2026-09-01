<?php

use App\Filament\Pages\Laporan;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Livewire\Livewire;

function buatTransaksiLaporan(User $user, BukuKas $bukuKas, JenisTransaksi $kategori, string $jenis, int $nominal, string $tanggal): Transaksi
{
    return Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]));
}

test('laporan bulanan menghitung saldo dan membatasi data milik pengguna', function () {
    $user = User::factory()->create(['role' => 'user']);
    $bukuKas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama', 'saldo' => 1150]);
    $kategoriMasuk = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Gaji', 'tipe' => 'Pemasukan']);
    $kategoriKeluar = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Belanja', 'tipe' => 'Pengeluaran']);

    buatTransaksiLaporan($user, $bukuKas, $kategoriMasuk, 'Pemasukan', 100, '2026-01-10 10:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategoriMasuk, 'Pemasukan', 500, '2026-08-05 10:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategoriKeluar, 'Pengeluaran', 200, '2026-08-07 10:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategoriMasuk, 'Pemasukan', 750, '2026-09-01 10:00:00');

    $userLain = User::factory()->create(['role' => 'user']);
    $bukuKasLain = BukuKas::create(['user_id' => $userLain->id, 'nama_buku' => 'Kas Lain', 'saldo' => 9999]);
    $kategoriLain = JenisTransaksi::create(['user_id' => $userLain->id, 'nama_jenis' => 'Rahasia', 'tipe' => 'Pemasukan']);
    buatTransaksiLaporan($userLain, $bukuKasLain, $kategoriLain, 'Pemasukan', 9999, '2026-08-05 10:00:00');

    $komponen = Livewire::actingAs($user)
        ->test(Laporan::class)
        ->set('bulan', '08')
        ->set('tahun', 2026)
        ->assertSuccessful();

    $laporan = $komponen->instance()->dataLaporan;

    expect($laporan)
        ->saldoAwal->toBe(100)
        ->pemasukan->toBe(500)
        ->pengeluaran->toBe(200)
        ->akumulasi->toBe(300)
        ->saldoAkhir->toBe(400)
        ->and($laporan['kategoriPemasukan'][0]['nama'])->toBe('Gaji')
        ->and($laporan['kategoriPengeluaran'][0]['nama'])->toBe('Belanja');
});

test('laporan mendukung periode harian tahunan custom dan navigasi periode', function () {
    $user = User::factory()->create(['role' => 'user']);
    $bukuKas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama', 'saldo' => 600]);
    $kategori = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Proyek', 'tipe' => 'Pemasukan']);
    buatTransaksiLaporan($user, $bukuKas, $kategori, 'Pemasukan', 100, '2026-08-01 09:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategori, 'Pemasukan', 200, '2026-08-15 09:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategori, 'Pemasukan', 300, '2026-09-01 09:00:00');

    $komponen = Livewire::actingAs($user)->test(Laporan::class)
        ->call('pilihPeriode', 'harian')
        ->set('tanggalAcuan', '2026-08-15');
    expect($komponen->instance()->dataLaporan['pemasukan'])->toBe(200);

    $komponen->call('pilihPeriode', 'tahunan')
        ->set('tahun', 2026);
    expect($komponen->instance()->dataLaporan['pemasukan'])->toBe(600);

    $komponen->call('pilihPeriode', 'custom')
        ->set('tanggalMulai', '2026-08-02')
        ->set('tanggalSelesai', '2026-08-31');
    expect($komponen->instance()->dataLaporan['pemasukan'])->toBe(200);

    $komponen->call('pilihPeriode', 'bulanan')
        ->set('bulan', '08')
        ->set('tahun', 2026)
        ->call('geserPeriode', 1);
    expect($komponen->get('bulan'))->toBe('09')
        ->and($komponen->get('tahun'))->toBe(2026)
        ->and($komponen->instance()->dataLaporan['pemasukan'])->toBe(300);
});

test('laporan bulanan dan tahunan menggunakan pilihan periode tanpa input tanggal', function () {
    $user = User::factory()->create(['role' => 'user']);
    BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama', 'saldo' => 0]);

    Livewire::actingAs($user)
        ->test(Laporan::class)
        ->assertSuccessful()
        ->assertDontSeeHtml('aria-label="Tanggal acuan"')
        ->assertSeeHtml('aria-label="Bulan"')
        ->assertSeeHtml('aria-label="Tahun"')
        ->call('pilihPeriode', 'tahunan')
        ->assertDontSeeHtml('aria-label="Tanggal acuan"')
        ->assertDontSeeHtml('aria-label="Bulan"')
        ->assertSeeHtml('aria-label="Tahun"');
});
