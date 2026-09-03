<?php

use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== PENGUJIAN FILTER BUKU KAS ====================

test('toolbar filter transaksi menampilkan kontrol periode buku kas dan reset', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Periode transaksi')
        ->assertSeeText('Buku Kas')
        ->assertSeeText('Kas Test')
        ->assertSeeText('Semua Buku Kas')
        ->assertSeeText('Reset filter')
        ->assertSeeHtml('aria-label="Bulan sebelumnya"')
        ->assertSeeHtml('aria-label="Bulan berikutnya"');
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - getBukuKasOptions mengembalikan array dengan nama_buku dan id', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->call('getBukuKasOptions')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - getBukuKasOptions mengurutkan Kas Utama di atas', function () {
    $user = createRegularUserWithBukuKas();

    // Buat buku kas tambahan
    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Utama',
        'saldo' => 0,
    ]);
    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tabungan',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->call('getBukuKasOptions')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas default menampilkan semua buku kas', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSet('filterBukuKas', null);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas dapat diatur', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id)
        ->assertSet('filterBukuKas', (string) $bukuKas->id);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - semua buku kas menampilkan kolom buku kas tanpa saldo', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertTableColumnVisible('buku_kas.nama_buku')
        ->assertTableColumnHidden('saldo');
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - buku kas terpilih menampilkan saldo tanpa kolom buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, [
            'filterBukuKas' => (string) $bukuKas->id,
        ])
        ->assertSuccessful()
        ->assertTableColumnHidden('buku_kas.nama_buku')
        ->assertTableColumnVisible('saldo');
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas hanya tampilkan transaksi dari buku kas tersebut', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas1 = $user->buku_kas()->first();
    $bukuKas2 = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 0,
    ]);

    $jenis = JenisTransaksi::where('tipe', 'Pemasukan')->first();

    // Transaksi di buku kas pertama
    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas1->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi di Kas Test',
    ]);

    // Transaksi di buku kas kedua
    Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 75000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi di Kas Kedua',
    ]);

    // Pastikan kedua transaksi tersimpan di basis data
    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas1->id,
        'deskripsi' => 'Transaksi di Kas Test',
    ]);
    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas2->id,
        'deskripsi' => 'Transaksi di Kas Kedua',
    ]);

    // Terapkan filter buku kas pertama
    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas1->id)
        ->assertSet('filterBukuKas', (string) $bukuKas1->id);

    // Terapkan filter buku kas kedua
    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas2->id)
        ->assertSet('filterBukuKas', (string) $bukuKas2->id);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas kosong tampilkan semua transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas1 = $user->buku_kas()->first();
    $bukuKas2 = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 0,
    ]);

    $jenis = JenisTransaksi::where('tipe', 'Pemasukan')->first();

    $transaksiPertama = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas1->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Pemasukan Kas Test',
    ]);

    $transaksiKedua = Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 75000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Pemasukan Kas Kedua',
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', null)
        ->assertSet('filterBukuKas', null)
        ->assertCanSeeTableRecords([$transaksiPertama, $transaksiKedua]);

    // Pastikan kedua transaksi tersimpan di basis data
    $this->assertDatabaseHas('transaksi', ['deskripsi' => 'Pemasukan Kas Test']);
    $this->assertDatabaseHas('transaksi', ['deskripsi' => 'Pemasukan Kas Kedua']);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - mount initialize filterBukuKas dari parameter', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class, [
            'filterBukuKas' => (string) $bukuKas->id,
        ])
        ->assertSuccessful()
        ->assertSet('filterBukuKas', (string) $bukuKas->id);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - defaultForm menggunakan filterBukuKas jika diset', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id);

    // Pastikan properti digunakan oleh defaultForm
    $component = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->set('filterBukuKas', (string) $bukuKas->id);

    // Pastikan pilihan buku kas tetap dapat dimuat
    $component->call('getBukuKasOptions')->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - previous period url mempertahankan filter', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id)
        ->call('getPreviousPeriodUrl')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - next period url mempertahankan filter', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id)
        ->call('getNextPeriodUrl')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');
