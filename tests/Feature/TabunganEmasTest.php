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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function siapkanAkunTabunganEmas(): array
{
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'saldo' => 10000000, 'is_default' => true]);
    $pemasukan = JenisTransaksi::factory()->create(['user_id' => $user->id, 'nama_jenis' => 'Penjualan Emas', 'tipe' => 'Pemasukan']);
    $pengeluaran = JenisTransaksi::factory()->create(['user_id' => $user->id, 'nama_jenis' => 'Pembelian Emas', 'tipe' => 'Pengeluaran']);
    $tabungan = TabunganEmas::factory()->create(['buku_kas_id' => $kas->id, 'label' => 'Antam']);

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

test('halaman tabungan emas membuat label dan berat desimal dengan histori saldo awal', function () {
    $data = siapkanAkunTabunganEmas();

    Livewire::actingAs($data['user'])
        ->test(ListTabunganEmas::class)
        ->assertSuccessful()
        ->callAction('create', data: [
            'buku_kas_id' => $data['kas']->id,
            'label' => 'Emas Antam',
            'berat_gram' => '0,5',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('tabungan_emas', [
        'buku_kas_id' => $data['kas']->id,
        'label' => 'Emas Antam',
        'berat_gram' => 0.5,
    ]);
    $tabungan = TabunganEmas::where('label', 'Emas Antam')->firstOrFail();
    expect((float) $tabungan->transaksiEmas()->sole()->berat_gram)->toBe(0.5)
        ->and($data['kas']->fresh()->saldo)->toBe(100000);
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

test('form tabungan emas memakai label dan urutan berat tanpa nilai default', function () {
    $data = siapkanAkunTabunganEmas();
    $page = Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->assertTableColumnExists('label')
        ->assertTableColumnDoesNotExist('nama')
        ->assertTableColumnDoesNotExist('merek')
        ->assertTableColumnDoesNotExist('produk')
        ->mountAction('create')
        ->assertActionDataSet(['berat_gram' => null])
        ->assertFormFieldExists('label', fn ($field) => $field->getLabel() === 'Label emas' && $field->getPlaceholder() === 'Emas Antam')
        ->assertFormFieldExists('berat_gram', fn ($field) => $field->getPlaceholder() === 'Contoh: 0,5')
        ->assertFormFieldDoesNotExist('nama')
        ->assertFormFieldDoesNotExist('merek')
        ->assertFormFieldDoesNotExist('produk')
        ->assertFormFieldDoesNotExist('kadar');

    expect(Schema::hasColumn('tabungan_emas', 'kadar'))->toBeFalse();

    expect(array_map(fn ($field) => $field->getName(), $page->instance()->getSchema($page->instance()->getMountedActionSchemaName())->getComponents()))
        ->toBe(['buku_kas_id', 'berat_gram', 'label', 'harga_beli', 'created_at', 'keterangan']);
})->group('tabungan-emas', 'filament-emas');

test('form tabungan emas menolak berat kosong atau tidak valid', function ($berat) {
    $data = siapkanAkunTabunganEmas();
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->callAction('create', data: [
            'buku_kas_id' => $data['kas']->id,
            'label' => 'Emas gagal',
            'berat_gram' => $berat,
        ])->assertHasActionErrors(['berat_gram']);
    $this->assertDatabaseMissing('tabungan_emas', ['label' => 'Emas gagal']);
})->with([null, '0', '-0,5', 'abc', '0,00001'])->group('tabungan-emas', 'filament-emas');

test('edit label tabungan emas mempertahankan berat dan histori', function () {
    $data = siapkanAkunTabunganEmas();
    app(TabunganEmasService::class)->catatSaldoAwal($data['user'], $data['tabungan'], 0.5, 500000);
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->callTableAction('edit', $data['tabungan'], data: ['label' => 'Emas Antam baru', 'berat_gram' => 99])

        ->assertHasNoTableActionErrors();
    expect($data['tabungan']->fresh()->label)->toBe('Emas Antam baru')
        ->and((float) $data['tabungan']->fresh()->berat_gram)->toBe(0.5)
        ->and($data['tabungan']->transaksiEmas()->count())->toBe(1);
})->group('tabungan-emas', 'filament-emas');

test('tabel tabungan emas menampilkan tanggal dan berat tanpa nol desimal berlebih serta grup kas', function () {
    $data = siapkanAkunTabunganEmas();
    $records = collect(['1.0000', '0.5000', '1.2500', '0.0001', '1000.0000'])->map(
        fn ($berat) => TabunganEmas::factory()->create(['buku_kas_id' => $data['kas']->id, 'berat_gram' => $berat])
    );
    $kasLain = BukuKas::factory()->create(['nama_buku' => $data['kas']->nama_buku]);
    ShareBuku::factory()->create(['buku_kas_id' => $kasLain->id, 'user_id' => $data['user']->id, 'privilege' => 'viewer']);
    $emasLain = TabunganEmas::factory()->create(['buku_kas_id' => $kasLain->id]);
    $page = Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->assertTableColumnExists('created_at', fn ($column) => $column->getLabel() === 'Dibeli pada')
        ->assertTableColumnDoesNotExist('bukuKas.nama_buku')
        ->assertTableActionDoesNotExist('beli')
        ->assertTableActionDoesNotExist('jual')
        ->assertTableActionDoesNotExist('histori')
        ->assertTableColumnDoesNotExist('total_modal')
        ->assertCanSeeTableRecords($records->push($emasLain));
    foreach (['1 gram', '0,5 gram', '1,25 gram', '0,0001 gram', '1.000 gram'] as $index => $formatted) {
        $page->assertTableColumnFormattedStateSet('berat_gram', $formatted, $records[$index]);
    }
    $page->assertTableColumnFormattedStateSet('created_at', $records[0]->created_at->format('d M Y H:i'), $records[0]);
    $group = $page->instance()->getTable()->getDefaultGroup();
    expect($group->getId())->toBe('buku_kas_id')
        ->and($group->getTitle($records[0]))->toBe($data['kas']->nama_buku)
        ->and($group->getStringKey($records[0]))->not->toBe($group->getStringKey($emasLain));
})->group('tabungan-emas', 'filament-emas');

test('tabungan emas menyimpan harga beli dan keterangan opsional', function ($harga, $keterangan) {
    $data = siapkanAkunTabunganEmas();
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->callAction('create', data: [
            'buku_kas_id' => $data['kas']->id,
            'berat_gram' => '1,5',
            'label' => 'Emas dengan detail',
            'harga_beli' => $harga,
            'keterangan' => $keterangan,
        ])->assertHasNoActionErrors();
    $this->assertDatabaseHas('tabungan_emas', [
        'label' => 'Emas dengan detail',
        'harga_beli' => $harga,
        'keterangan' => $keterangan,
    ]);
})->with([[null, null], [1500000, 'Disimpan di brankas']])->group('tabungan-emas', 'filament-emas');

test('tabungan emas menyimpan dan mengubah tanggal dibeli pada', function () {
    $data = siapkanAkunTabunganEmas();
    $tanggal = now()->subDays(10)->startOfMinute();
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->mountAction('create')
        ->assertFormFieldExists('created_at', fn ($field) => $field->getLabel() === 'Dibeli pada')
        ->setActionData([
            'buku_kas_id' => $data['kas']->id,
            'label' => 'Emas bertanggal',
            'berat_gram' => '0,5',
            'created_at' => $tanggal->format('Y-m-d H:i:s'),
        ])->callMountedAction()->assertHasNoActionErrors();
    $emas = TabunganEmas::where('label', 'Emas bertanggal')->firstOrFail();
    expect($emas->created_at->equalTo($tanggal))->toBeTrue();
    $tanggalBaru = $tanggal->copy()->addDay();
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->callTableAction('edit', $emas, data: ['created_at' => $tanggalBaru->format('Y-m-d H:i:s')])
        ->assertHasNoTableActionErrors()
        ->assertTableColumnFormattedStateSet('created_at', $tanggalBaru->format('d M Y H:i'), $emas);
    expect($emas->fresh()->created_at->equalTo($tanggalBaru))->toBeTrue();
})->group('tabungan-emas', 'filament-emas');

test('tabungan emas menolak tanggal pembelian kosong atau di masa depan', function ($tanggal) {
    $data = siapkanAkunTabunganEmas();
    Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)
        ->callAction('create', data: [
            'buku_kas_id' => $data['kas']->id,
            'label' => 'Tanggal tidak valid',
            'berat_gram' => '1',
            'created_at' => $tanggal === 'besok' ? now()->addDay()->format('Y-m-d H:i:s') : null,
        ])->assertHasActionErrors(['created_at']);
    $this->assertDatabaseMissing('tabungan_emas', ['label' => 'Tanggal tidak valid']);
})->with([null, 'besok'])->group('tabungan-emas', 'filament-emas');

test('grup kas menampilkan total gram seluruh tabungan tanpa tercampur kas lain', function () {
    $data = siapkanAkunTabunganEmas();
    $data['tabungan']->update(['berat_gram' => 0.5, 'label' => 'Emas dicari']);
    TabunganEmas::factory()->count(11)->create(['buku_kas_id' => $data['kas']->id, 'berat_gram' => 0.25]);
    $kasLain = BukuKas::factory()->create(['nama_buku' => $data['kas']->nama_buku]);
    ShareBuku::factory()->create(['buku_kas_id' => $kasLain->id, 'user_id' => $data['user']->id, 'privilege' => 'viewer']);
    $emasLain = TabunganEmas::factory()->create(['buku_kas_id' => $kasLain->id, 'berat_gram' => 2]);
    $page = Livewire::actingAs($data['user'])->test(ListTabunganEmas::class)->assertSuccessful();
    $group = $page->instance()->getTable()->getDefaultGroup();
    $records = $page->instance()->getFilteredTableQuery()->get()->keyBy('id');
    expect($group->getDescription($records[$data['tabungan']->id], null))->toBe('Total berat: 3,25 gram')
        ->and($group->getDescription($records[$emasLain->id], null))->toBe('Total berat: 2 gram');
    $page->searchTable('Emas dicari')->assertCanSeeTableRecords([$data['tabungan']]);
    $record = $page->instance()->getFilteredTableQuery()->sole();
    expect($group->getDescription($record, null))->toBe('Total berat: 3,25 gram');
})->group('tabungan-emas', 'filament-emas');
