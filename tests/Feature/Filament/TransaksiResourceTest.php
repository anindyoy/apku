<?php

use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ==================== TRANSAKSI RESOURCE ====================

test('transaksi resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp');
})
    ->group('filament', 'transaksi');

test('kolom aktivitas transaksi selalu diawali huruf kapital', function () {
    $user = createRegularUserWithBukuKas();
    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'transfer',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'jenis' => 'Pemasukan',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    expect(TransaksiResource::getKategoriLabel($transaksi))->toBe('Transfer');
})
    ->group('filament', 'transaksi', 'aktivitas-kapital');

test('warna record transaksi dibedakan berdasarkan tipe transaksi', function () {
    expect(TransaksiResource::getWarnaTipeTransaksi('Pemasukan'))->toBe('danger')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Pengeluaran'))->toBe('success')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pemasukan'))->toBe('primary')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pengeluaran'))->toBe('primary');
})
    ->group('filament', 'transaksi', 'warna-tipe-transaksi');

test('tabel transaksi merangkum pencatat dan dompet pada deskripsi kolom', function () {
    $user = createRegularUserWithBukuKas();
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'jenis' => 'Pemasukan',
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertCanSeeTableRecords([$transaksi])
        ->html();

    expect($html)
        ->toContain('Dicatat oleh: '.$user->name)
        ->toContain('Dompet: '.$transaksi->labelDompetUntuk($user));
})
    ->group('filament', 'transaksi', 'ringkasan-kolom');

test('transaksi resource dapat mengedit nominal transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', 150000)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(150000, $transaksi->fresh()->nominal);
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat menghapus transaksi pemasukan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->callTableAction('delete', $transaksi->id);

    $this->assertEmpty(DB::table('transaksi')->find($transaksi->id));
})
    ->group('filament', 'transaksi');
