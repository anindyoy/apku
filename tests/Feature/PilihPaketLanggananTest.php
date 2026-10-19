<?php

use App\Enums\StatusLangganan;
use App\Livewire\PilihPaketLangganan;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

// Membersihkan data demo agar skenario uji terkendali.
function bersihkanDataPaketUntukUji(): void
{
    Langganan::query()->delete();
    VoucherCode::query()->delete();
    Voucher::query()->delete();
    PaketLangganan::query()->delete();
    MetodePembayaran::query()->delete();
}

test('grid hanya menampilkan paket aktif dengan sorotan paling hemat dan kelas responsif', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $satuBulan = PaketLangganan::factory()->create(['label' => 'Paket 1 Bulan', 'harga' => 20000, 'durasi_hari' => 30, 'is_active' => true]);
    $tigaBulan = PaketLangganan::factory()->create(['label' => 'Paket 3 Bulan', 'harga' => 57000, 'durasi_hari' => 90, 'is_active' => true]);
    $enamBulan = PaketLangganan::factory()->create(['label' => 'Paket 6 Bulan', 'harga' => 108000, 'durasi_hari' => 180, 'is_active' => true]);
    $sembilanBulan = PaketLangganan::factory()->create(['label' => 'Paket 9 Bulan', 'harga' => 153000, 'durasi_hari' => 270, 'is_active' => true]);
    $satuTahun = PaketLangganan::factory()->create(['label' => 'Paket 1 Tahun', 'harga' => 192000, 'durasi_hari' => 365, 'is_active' => true]);
    $arsip = PaketLangganan::factory()->create(['label' => 'Paket Arsip', 'harga' => 10000, 'durasi_hari' => 30, 'is_active' => false]);
    MetodePembayaran::factory()->create(['is_active' => true]);

    $html = Livewire::actingAs($user)->test(PilihPaketLangganan::class)->assertSuccessful()->html();

    // Paket aktif tampil, paket arsip tidak tampil.
    foreach ([$satuBulan, $tigaBulan, $enamBulan, $sembilanBulan, $satuTahun] as $paket) {
        expect($html)->toContain($paket->label);
    }
    expect($html)->not->toContain($arsip->label);

    // Urutan terpanjang dulu: 1 tahun muncul sebelum 3 bulan.
    expect(strpos($html, 'Paket 1 Tahun') < strpos($html, 'Paket 3 Bulan'))->toBeTrue();

    // Satu kartu disorot sebagai paling hemat dengan badge.
    expect(substr_count($html, 'Paling hemat'))->toBe(1);
    expect($html)->toContain('data-grid-paket')
        ->and($html)->toContain('grid-cols-1')
        ->and($html)->toContain('min-[481px]:grid-cols-2')
        ->and($html)->toContain('lg:grid-cols-3')
        ->and($html)->toContain('xl:grid-cols-5');

    // Tombol beli memakai penanda anti order ganda.
    expect($html)->toContain('data-paket-beli="'.$satuTahun->id.'"')
        ->and($html)->toContain('wire:loading.attr="disabled"');
})->group('langganan');

test('state kosong menampilkan pesan belum ada paket', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);

    $html = Livewire::actingAs($user)->test(PilihPaketLangganan::class)->assertSuccessful()->html();

    expect($html)->toContain('Belum ada paket yang tersedia.');
})->group('langganan');

test('memilih paket membuka modal dengan ringkasan benar dan metode bawaan', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    PaketLangganan::factory()->create(['label' => 'Paket 1 Bulan', 'harga' => 20000, 'durasi_hari' => 30, 'is_active' => true]);
    $satuTahun = PaketLangganan::factory()->create(['label' => 'Paket 1 Tahun', 'harga' => 192000, 'durasi_hari' => 365, 'is_active' => true]);
    $metode = MetodePembayaran::factory()->create(['label' => 'Transfer Bank', 'is_active' => true]);

    $komponen = Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $satuTahun->id)
        ->assertSuccessful()
        ->assertSet('modalTerbuka', true)
        ->assertSet('paketId', $satuTahun->id)
        ->assertSet('metodeId', $metode->id);

    $html = $komponen->html();
    expect($html)->toContain('data-modal-paket')
        ->and($html)->toContain('role="dialog"')
        ->and($html)->toContain('Konfirmasi order')
        ->and($html)->toContain('Paket 1 Tahun')
        ->and($html)->toContain('Rp192.000')
        ->and($html)->toContain('Total bayar')
        ->and($html)->toContain('data-ringkasan')
        ->and($html)->toContain('Membuat order...');
})->group('langganan');

test('voucher valid, tidak ditemukan, kedaluwarsa, dan sudah dipakai memberi pesan spesifik', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['harga' => 100000, 'durasi_hari' => 30, 'is_active' => true]);
    MetodePembayaran::factory()->create(['is_active' => true]);

    $voucherValid = Voucher::factory()->create(['jumlah_diskon' => 20, 'masa_aktif' => today()->addMonth(), 'dapat_dipakai_berulang' => true]);
    $kodeValid = VoucherCode::factory()->create(['voucher_id' => $voucherValid->id, 'code' => 'HEMAT20UJI']);

    // Kode valid memakai diskon dan memperbarui ringkasan.
    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', 'hemat20uji')
        ->call('terapkanVoucher')
        ->assertSuccessful()
        ->assertSet('voucherValid', true)
        ->assertSet('voucherPersen', 20)
        ->assertSet('pesanVoucher', 'Voucher dipakai: diskon 20%.');

    // Kode tidak ditemukan.
    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', 'TIDAKADA123')
        ->call('terapkanVoucher')
        ->assertSuccessful()
        ->assertSet('voucherValid', false)
        ->assertSet('pesanVoucher', 'Kode voucher tidak ditemukan. Periksa penulisannya.');

    // Kode kedaluwarsa.
    $voucherKedaluwarsa = Voucher::factory()->create(['jumlah_diskon' => 10, 'masa_aktif' => today()->subDay(), 'dapat_dipakai_berulang' => true]);
    $kodeKedaluwarsa = VoucherCode::factory()->create(['voucher_id' => $voucherKedaluwarsa->id]);
    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', $kodeKedaluwarsa->code)
        ->call('terapkanVoucher')
        ->assertSuccessful()
        ->assertSet('voucherValid', false)
        ->assertSet('pesanVoucher', 'Voucher sudah kedaluwarsa.');

    // Kode sekali pakai yang sudah dipakai order aktif.
    $voucherSekali = Voucher::factory()->create(['jumlah_diskon' => 10, 'masa_aktif' => today()->addMonth(), 'dapat_dipakai_berulang' => false]);
    $kodeSekali = VoucherCode::factory()->create(['voucher_id' => $voucherSekali->id]);
    Langganan::factory()->create([
        'user_id' => $user->id,
        'voucher_code_id' => $kodeSekali->id,
        'status' => StatusLangganan::MenungguPembayaran,
    ]);
    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', $kodeSekali->code)
        ->call('terapkanVoucher')
        ->assertSuccessful()
        ->assertSet('voucherValid', false)
        ->assertSet('pesanVoucher', 'Kode voucher sudah pernah dipakai.');
})->group('langganan');

test('mengubah kode voucher mereset diskon basi agar ringkasan tidak keliru', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['harga' => 100000, 'durasi_hari' => 30, 'is_active' => true]);
    MetodePembayaran::factory()->create(['is_active' => true]);
    $voucher = Voucher::factory()->create(['jumlah_diskon' => 20, 'masa_aktif' => today()->addMonth(), 'dapat_dipakai_berulang' => true]);
    VoucherCode::factory()->create(['voucher_id' => $voucher->id, 'code' => 'RESET20UJI']);

    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', 'RESET20UJI')
        ->call('terapkanVoucher')
        ->assertSet('voucherValid', true)
        ->set('kodeVoucher', 'RESET20UJI-UBAH')
        ->assertSet('voucherValid', false)
        ->assertSet('voucherPersen', 0)
        ->assertSet('pesanVoucher', '');
})->group('langganan');

test('order paket menyimpan snapshot dan total dihitung server walau klien dimanipulasi', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['label' => 'Paket 1 Tahun', 'harga' => 192000, 'durasi_hari' => 365, 'is_active' => true]);
    $metode = MetodePembayaran::factory()->create(['label' => 'Transfer Bank', 'nama_penyedia' => 'Bank Uji', 'nomor_tujuan' => '998877', 'is_active' => true]);
    $voucher = Voucher::factory()->create(['jumlah_diskon' => 20, 'masa_aktif' => today()->addMonth(), 'dapat_dipakai_berulang' => true]);
    $kode = VoucherCode::factory()->create(['voucher_id' => $voucher->id, 'code' => 'SERVER20UJI']);

    $komponen = Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', $kode->code)
        ->call('terapkanVoucher')
        // Manipulasi persen dari browser harus diabaikan; total tetap memakai voucher valid 20%.
        ->set('voucherPersen', 100)
        ->call('buatOrder')
        ->assertSuccessful()
        ->assertHasNoErrors()
        ->assertDispatched('order-langganan-dibuat');

    $order = Langganan::query()->where('user_id', $user->id)->latest('id')->first();
    expect($order)->not->toBeNull()
        ->and($order->label_paket)->toBe('Paket 1 Tahun')
        ->and($order->harga)->toBe(192000)
        ->and($order->durasi_hari)->toBe(365)
        ->and($order->label_metode_pembayaran)->toBe('Transfer Bank')
        ->and($order->detail_pembayaran['nomor_tujuan'])->toBe('998877')
        ->and($order->kode_voucher)->toBe('SERVER20UJI')
        ->and($order->persentase_diskon)->toBe(20)
        ->and($order->nominal_diskon)->toBe(38400)
        ->and($order->total_pembayaran)->toBe(153600)
        ->and($order->status)->toBe(StatusLangganan::MenungguPembayaran);

    // State direset sehingga panggilan ganda tidak membuat order kedua.
    $komponen->assertSet('modalTerbuka', false)->assertSet('paketId', null);
    $jumlahSebelum = Langganan::query()->where('user_id', $user->id)->count();
    $komponen->call('buatOrder');
    expect(Langganan::query()->where('user_id', $user->id)->count())->toBe($jumlahSebelum);
})->group('langganan');

test('admin ditolak, paket nonaktif dan metode nonaktif gagal dengan pesan jelas', function () {
    bersihkanDataPaketUntukUji();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    $metode = MetodePembayaran::factory()->create(['is_active' => true]);

    // Admin tidak boleh membuat order lewat komponen ini.
    Livewire::actingAs($admin)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->call('buatOrder')
        ->assertForbidden();

    // Paket yang dinonaktifkan setelah modal dibuka tidak bisa dipesan.
    $komponen = Livewire::actingAs($user)->test(PilihPaketLangganan::class)->call('pilihPaket', $paket->id);
    $paket->update(['is_active' => false]);
    $komponen->call('buatOrder')->assertHasErrors(['paketId']);
    expect(Langganan::query()->where('user_id', $user->id)->count())->toBe(0);

    // Metode yang dinonaktifkan setelah modal dibuka meminta pilih ulang.
    $paket->update(['is_active' => true]);
    $komponenBaru = Livewire::actingAs($user)->test(PilihPaketLangganan::class)->call('pilihPaket', $paket->id);
    $metode->update(['is_active' => false]);
    $komponenBaru->call('buatOrder')->assertHasErrors(['metodeId']);
    expect(Langganan::query()->where('user_id', $user->id)->count())->toBe(0);
})->group('langganan');

test('kode voucher yang diketik salah saat order memberi galat tanpa membuat order', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    MetodePembayaran::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('kodeVoucher', 'SALAH123UJI')
        ->call('buatOrder')
        ->assertHasErrors(['kodeVoucher']);

    expect(Langganan::query()->where('user_id', $user->id)->count())->toBe(0);
})->group('langganan');

test('label voucher berulang dapat langsung diterapkan dan dibuatkan order pada modal pilih paket', function () {
    bersihkanDataPaketUntukUji();
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['harga' => 100000, 'durasi_hari' => 30, 'is_active' => true]);
    $metode = MetodePembayaran::factory()->create(['is_active' => true]);

    $voucher = Voucher::create([
        'label' => 'DISKONLANGSUNG',
        'jumlah_diskon' => 20,
        'dapat_dipakai_berulang' => true,
    ]);
    // Hapus kode yang otomatis dibuat untuk menguji fallback temukanUntukOrder
    $voucher->codes()->delete();

    Livewire::actingAs($user)->test(PilihPaketLangganan::class)
        ->call('pilihPaket', $paket->id)
        ->set('metodeId', $metode->id)
        ->set('kodeVoucher', 'diskonlangsung')
        ->call('terapkanVoucher')
        ->assertSuccessful()
        ->assertSet('voucherValid', true)
        ->assertSet('voucherPersen', 20)
        ->call('buatOrder')
        ->assertHasNoErrors();

    $order = Langganan::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->kode_voucher)->toBe('DISKONLANGSUNG')
        ->and($order->nominal_diskon)->toBe(20000)
        ->and($order->total_pembayaran)->toBe(80000);
})->group('langganan');
