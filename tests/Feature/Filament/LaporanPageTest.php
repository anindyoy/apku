<?php

use App\Filament\Pages\Laporan;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Livewire\Livewire;

function buatTransaksiLaporan(User $user, BukuKas $bukuKas, JenisTransaksi $kategori, string $jenis, int $nominal, string $tanggal): Transaksi
{
    $dompet = Dompet::withoutGlobalScopes()->firstOrCreate(
        ['user_id' => $user->id, 'nama_dompet' => 'Cash'],
        ['saldo' => 0, 'is_default' => true]
    );

    return Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompet->id,
        'jenis_transaksi_id' => $kategori->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]));
}

function buatTransaksiLaporanPadaDompet(
    User $user,
    BukuKas $bukuKas,
    Dompet $dompet,
    ?JenisTransaksi $kategori,
    string $jenis,
    int $nominal,
    string $tanggal,
    ?string $tipeTransfer = null,
): Transaksi {
    return Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompet->id,
        'jenis_transaksi_id' => $kategori?->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
        'tipe_transfer' => $tipeTransfer,
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

test('laporan dapat diunduh sebagai pdf dan excel sesuai filter aktif', function () {
    $user = User::factory()->create(['role' => 'user']);
    $bukuKas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Ekspor', 'saldo' => 750]);
    $kategori = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Bonus', 'tipe' => 'Pemasukan']);
    buatTransaksiLaporan($user, $bukuKas, $kategori, 'Pemasukan', 750, '2026-08-15 09:00:00');
    buatTransaksiLaporan($user, $bukuKas, $kategori, 'Pemasukan', 999, '2026-09-15 09:00:00');

    Livewire::actingAs($user)
        ->test(Laporan::class)
        ->set('bukuKasId', (string) $bukuKas->id)
        ->set('bulan', '08')
        ->set('tahun', 2026)
        ->assertSeeHtml('aria-label="Export laporan"')
        ->call('unduhPdf')
        ->assertFileDownloaded('laporan-bulanan-20260801-20260831.pdf');

    Livewire::actingAs($user)
        ->test(Laporan::class)
        ->set('bukuKasId', (string) $bukuKas->id)
        ->set('bulan', '08')
        ->set('tahun', 2026)
        ->call('unduhExcel')
        ->assertFileDownloaded('laporan-bulanan-20260801-20260831.xlsx');
});

test('laporan memfilter dompet dan tidak menghitung transfer dompet sebagai arus kas', function () {
    $user = User::factory()->create(['role' => 'user']);
    $bukuKas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama', 'saldo' => 1300]);
    $cash = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 700, 'is_default' => true]);
    $bank = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 600]);
    $kategori = JenisTransaksi::create(['user_id' => $user->id, 'nama_jenis' => 'Gaji', 'tipe' => 'Pemasukan']);

    buatTransaksiLaporanPadaDompet($user, $bukuKas, $cash, $kategori, 'Pemasukan', 1000, '2026-08-01 09:00:00');
    buatTransaksiLaporanPadaDompet($user, $bukuKas, $cash, null, 'Transfer Pengeluaran', 300, '2026-08-10 09:00:00', 'dompet');
    buatTransaksiLaporanPadaDompet($user, $bukuKas, $bank, null, 'Transfer Pemasukan', 300, '2026-08-10 09:00:00', 'dompet');
    buatTransaksiLaporanPadaDompet($user, $bukuKas, $bank, $kategori, 'Pemasukan', 300, '2026-08-15 09:00:00');

    $komponen = Livewire::actingAs($user)
        ->test(Laporan::class)
        ->set('dompetId', (string) $cash->id)
        ->set('bulan', '08')
        ->set('tahun', 2026);

    $laporan = $komponen->instance()->dataLaporan;

    expect($laporan['transaksi'])->toHaveCount(2)
        ->and($laporan['pemasukan'])->toBe(1000)
        ->and($laporan['pengeluaran'])->toBe(0)
        ->and($laporan['akumulasi'])->toBe(1000)
        ->and($laporan['saldoAwal'])->toBe(0)
        ->and($laporan['saldoAkhir'])->toBe(700)
        ->and($laporan['kategoriPemasukan'])->toHaveCount(1)
        ->and($laporan['kategoriPengeluaran'])->toBeEmpty();
});

test('laporan dengan semua dompet menjaga transfer dompet tetap netral', function () {
    $user = User::factory()->create(['role' => 'user']);
    $bukuKas = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama', 'saldo' => 1000]);
    $cash = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 600, 'is_default' => true]);
    $bank = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 400]);

    buatTransaksiLaporanPadaDompet($user, $bukuKas, $cash, null, 'Transfer Pengeluaran', 400, '2026-08-10 09:00:00', 'dompet');
    buatTransaksiLaporanPadaDompet($user, $bukuKas, $bank, null, 'Transfer Pemasukan', 400, '2026-08-10 09:00:00', 'dompet');

    $komponen = Livewire::actingAs($user)
        ->test(Laporan::class)
        ->set('bulan', '08')
        ->set('tahun', 2026);

    $laporan = $komponen->instance()->dataLaporan;

    expect($laporan['pemasukan'])->toBe(0)
        ->and($laporan['pengeluaran'])->toBe(0)
        ->and($laporan['akumulasi'])->toBe(0)
        ->and($laporan['saldoAwal'])->toBe(1000)
        ->and($laporan['saldoAkhir'])->toBe(1000)
        ->and($laporan['kategoriPemasukan'])->toBeEmpty()
        ->and($laporan['kategoriPengeluaran'])->toBeEmpty();
});
