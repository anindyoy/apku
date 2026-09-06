<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\TabunganEmasResource\Pages\ListTabunganEmas;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\HargaEmas;
use App\Models\JenisTransaksi;
use App\Models\ShareBuku;
use App\Models\TabunganEmas;
use App\Models\User;
use App\Services\HargaEmasService;
use App\Services\TabunganEmasService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function siapkanAkunTabunganEmas(): array
{
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'saldo' => 10000000, 'is_default' => true]);
    $pemasukan = JenisTransaksi::factory()->create(['user_id' => $user->id, 'nama_jenis' => 'Penjualan Emas', 'tipe' => 'Pemasukan']);
    $pengeluaran = JenisTransaksi::factory()->create(['user_id' => $user->id, 'nama_jenis' => 'Pembelian Emas', 'tipe' => 'Pengeluaran']);
    $tabungan = TabunganEmas::factory()->create(['buku_kas_id' => $kas->id, 'nama' => 'Antam']);

    return compact('user', 'kas', 'dompet', 'pemasukan', 'pengeluaran', 'tabungan');
}

test('pembelian dan penjualan emas memperbarui aset serta saldo rupiah secara atomik', function () {
    $data = siapkanAkunTabunganEmas();
    $service = app(TabunganEmasService::class);

    $pembelian = $service->beli($data['user'], $data['tabungan'], $data['dompet'], [
        'berat_gram' => 2,
        'harga_per_gram' => 1000000,
        'biaya_tambahan' => 50000,
        'jenis_transaksi_id' => $data['pengeluaran']->id,
    ]);

    expect((float) $data['tabungan']->fresh()->berat_gram)->toBe(2.0)
        ->and($data['tabungan']->fresh()->total_modal)->toBe(2050000)
        ->and($data['kas']->fresh()->saldo)->toBe(-1950000)
        ->and($data['dompet']->fresh()->saldo)->toBe(7950000)
        ->and($pembelian->transaksi_id)->not->toBeNull();

    $service->jual($data['user'], $data['tabungan']->fresh(), $data['dompet']->fresh(), [
        'berat_gram' => 0.5,
        'harga_per_gram' => 1200000,
        'biaya_tambahan' => 10000,
        'jenis_transaksi_id' => $data['pemasukan']->id,
    ]);

    expect((float) $data['tabungan']->fresh()->berat_gram)->toBe(1.5)
        ->and($data['tabungan']->fresh()->total_modal)->toBe(1537500)
        ->and($data['kas']->fresh()->saldo)->toBe(-1360000)
        ->and($data['dompet']->fresh()->saldo)->toBe(8540000);
})->group('tabungan-emas', 'transaksi-emas');

test('penjualan emas melebihi berat yang dimiliki ditolak tanpa mengubah saldo', function () {
    $data = siapkanAkunTabunganEmas();
    app(TabunganEmasService::class)->catatSaldoAwal($data['user'], $data['tabungan'], 1, 900000);

    try {
        app(TabunganEmasService::class)->jual($data['user'], $data['tabungan']->fresh(), $data['dompet'], [
            'berat_gram' => 2,
            'harga_per_gram' => 1000000,
            'biaya_tambahan' => 0,
            'jenis_transaksi_id' => $data['pemasukan']->id,
        ]);
        $this->fail('Validasi berat seharusnya gagal.');
    } catch (ValidationException) {
        expect((float) $data['tabungan']->fresh()->berat_gram)->toBe(1.0)
            ->and($data['kas']->fresh()->saldo)->toBe(100000);
    }
})->group('tabungan-emas', 'transaksi-emas');

test('editor kas bersama dapat membeli dan menjual emas dengan dompet miliknya', function () {
    $data = siapkanAkunTabunganEmas();
    $editor = User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addMonth(), 'email_verified_at' => now()]);
    $dompetEditor = Dompet::factory()->create(['user_id' => $editor->id, 'saldo' => 3000000, 'is_default' => true]);
    $kategoriBeli = JenisTransaksi::factory()->create(['user_id' => $editor->id, 'nama_jenis' => 'Beli Emas', 'tipe' => 'Pengeluaran']);
    $kategoriJual = JenisTransaksi::factory()->create(['user_id' => $editor->id, 'nama_jenis' => 'Jual Emas', 'tipe' => 'Pemasukan']);
    ShareBuku::factory()->create(['buku_kas_id' => $data['kas']->id, 'user_id' => $editor->id, 'privilege' => 'editor']);

    app(TabunganEmasService::class)->beli($editor, $data['tabungan'], $dompetEditor, [
        'berat_gram' => 1,
        'harga_per_gram' => 1000000,
        'biaya_tambahan' => 0,
        'jenis_transaksi_id' => $kategoriBeli->id,
    ]);

    app(TabunganEmasService::class)->jual($editor, $data['tabungan']->fresh(), $dompetEditor->fresh(), [
        'berat_gram' => 0.25,
        'harga_per_gram' => 1200000,
        'biaya_tambahan' => 0,
        'jenis_transaksi_id' => $kategoriJual->id,
    ]);

    expect((float) $data['tabungan']->fresh()->berat_gram)->toBe(0.75)
        ->and($dompetEditor->fresh()->saldo)->toBe(2300000)
        ->and($data['tabungan']->transaksiEmas()->where('user_id', $editor->id)->count())->toBe(2);
})->group('tabungan-emas', 'kolaborasi-emas');

test('harga buyback dari api dinormalisasi dan harga manual hanya berlaku pada kas terkait', function () {
    Cache::clear();
    $data = siapkanAkunTabunganEmas();
    Http::fake([
        '*' => Http::sequence()->push([
            'success' => true,
            'data' => [[
                'source' => 'anekalogam',
                'displayName' => 'Aneka Logam',
                'material' => 'gold',
                'materialType' => 'LM Antam',
                'weight' => 2,
                'buybackPrice' => 2400000,
                'currency' => 'IDR',
                'recordedDate' => '2026-09-06',
            ]],
        ])->pushStatus(503)->pushStatus(503)->pushStatus(503),
    ]);

    $harga = app(HargaEmasService::class)->hargaBuyback($data['kas'], true);
    $manual = app(HargaEmasService::class)->simpanManual($data['kas'], $data['user'], 1250000);
    Cache::clear();
    $fallback = app(HargaEmasService::class)->hargaBuyback($data['kas'], true);

    expect($harga['harga_per_gram'])->toBe(1200000)
        ->and($harga['status'])->toBe('terbaru')
        ->and($manual['status'])->toBe('manual')
        ->and($fallback['harga_per_gram'])->toBe(1250000)
        ->and($fallback['status'])->toBe('manual')
        ->and(HargaEmas::where('sumber', 'manual')->where('buku_kas_id', $data['kas']->id)->count())->toBe(1);
})->group('tabungan-emas', 'harga-emas');

test('valuasi menggabungkan saldo rupiah dan nilai emas tanpa mengubah arus kas', function () {
    $data = siapkanAkunTabunganEmas();
    $data['tabungan']->update(['berat_gram' => 2.5, 'total_modal' => 2500000]);

    $nilai = app(TabunganEmasService::class)->valuasi($data['kas']->fresh(), 1200000);

    expect($nilai)->toMatchArray([
        'berat_gram' => 2.5,
        'nilai_emas' => 3000000,
        'saldo_rupiah' => 100000,
        'total_nilai_kas' => 3100000,
        'total_modal' => 2500000,
        'untung_rugi' => 500000,
    ])->and($data['kas']->fresh()->saldo)->toBe(100000);
})->group('tabungan-emas', 'valuasi-emas');

test('halaman tabungan emas dapat membuat produk dengan merek dan produk opsional', function () {
    $data = siapkanAkunTabunganEmas();

    Livewire::actingAs($data['user'])
        ->test(ListTabunganEmas::class)
        ->assertSuccessful()
        ->callAction('create', data: [
            'buku_kas_id' => $data['kas']->id,
            'nama' => 'Emas tanpa merek',
            'merek' => null,
            'produk' => null,
            'kadar' => 99.99,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('tabungan_emas', [
        'buku_kas_id' => $data['kas']->id,
        'nama' => 'Emas tanpa merek',
        'merek' => null,
        'produk' => null,
    ]);
})->group('tabungan-emas', 'filament-emas');

test('kas yang memiliki emas menyediakan aksi valuasi dan snapshot harga manual', function () {
    $data = siapkanAkunTabunganEmas();
    $data['tabungan']->update(['berat_gram' => 1]);

    Livewire::actingAs($data['user'])
        ->test(ListBukuKas::class)
        ->assertTableActionVisible('cekNilaiEmas', $data['kas'])
        ->callTableAction('hargaEmasManual', $data['kas'], data: ['harga_per_gram' => 1200000])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('harga_emas', [
        'buku_kas_id' => $data['kas']->id,
        'user_id' => $data['user']->id,
        'sumber' => 'manual',
        'harga_per_gram' => 1200000,
    ]);
})->group('tabungan-emas', 'filament-emas', 'harga-emas');

test('penghapusan kas memindahkan tabungan emas ke kas tujuan', function () {
    $data = siapkanAkunTabunganEmas();
    $kasTujuan = BukuKas::factory()->create([
        'user_id' => $data['user']->id,
        'nama_buku' => 'Kas Tujuan Emas',
        'saldo' => 50000,
    ]);
    $data['tabungan']->update(['berat_gram' => 1, 'total_modal' => 1000000]);

    Livewire::actingAs($data['user'])
        ->test(ListBukuKas::class)
        ->callTableAction('hapusDanPindahkan', $data['kas'], data: ['buku_kas_id' => $kasTujuan->id])
        ->assertHasNoTableActionErrors();

    expect($data['tabungan']->fresh()->buku_kas_id)->toBe($kasTujuan->id)
        ->and($kasTujuan->fresh()->saldo)->toBe(150000);
    $this->assertDatabaseMissing('buku_kas', ['id' => $data['kas']->id]);
})->group('tabungan-emas', 'hapus-kas-emas');
