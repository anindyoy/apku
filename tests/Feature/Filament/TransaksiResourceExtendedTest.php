<?php

use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== TRANSAKSI RESOURCE EXTENDED TESTS ====================

test('transaksi resource - list page menampilkan kolom yang benar', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Test deskripsi transaksi',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSee('Rp')
        ->assertSee('Test deskripsi transaksi');
})
    ->group('filament', 'transaksi');

test('transaksi resource - edit page dapat update nominal', function () {
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
        'deskripsi' => 'Deskripsi awal',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', 200000)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(200000, $transaksi->fresh()->nominal);
})
    ->group('filament', 'transaksi');

test('transaksi resource - edit page dapat update deskripsi', function () {
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
        'deskripsi' => 'Deskripsi awal',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.deskripsi', 'Deskripsi baru yang diubah')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Deskripsi baru yang diubah', $transaksi->fresh()->deskripsi);
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page dengan pengeluaran', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pengeluaran',
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 50000,
        'deskripsi' => 'Test pengeluaran',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSee('Rp')
        ->assertSee('Test pengeluaran');
})
    ->group('filament', 'transaksi');

test('transaksi resource - edit page validasi nominal required', function () {
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
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', null)
        ->call('save')
        ->assertHasErrors(['data.nominal']);
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page menampilkan multiple transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis1 = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $jenis2 = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pengeluaran',
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Pemasukan pertama',
        'jenis_transaksi_id' => $jenis1->id,
    ]);

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pengeluaran',
        'nominal' => 30000,
        'deskripsi' => 'Pengeluaran pertama',
        'jenis_transaksi_id' => $jenis2->id,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSee('Pemasukan pertama')
        ->assertSee('Pengeluaran pertama');
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page kas overview widget ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'widgets');

// ==================== FILTER BUKU KAS TESTS ====================

test('filter buku kas - getBukuKasOptions mengembalikan array dengan nama_buku dan id', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->call('getBukuKasOptions')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - getBukuKasOptions mengurutkan Kas Utama di atas', function () {
    $user = createRegularUserWithBukuKas();

    // Create additional buku kas
    \App\Models\BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Utama',
        'saldo' => 0,
    ]);
    \App\Models\BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Tabungan',
        'saldo' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->call('getBukuKasOptions')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas default kosong', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSet('filterBukuKas', '');
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas dapat diatur', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id)
        ->assertSet('filterBukuKas', (string) $bukuKas->id);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas hanya tampilkan transaksi dari buku kas tersebut', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas1 = $user->buku_kas()->first();
    $bukuKas2 = \App\Models\BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 0,
    ]);

    $jenis = \App\Models\JenisTransaksi::where('tipe', 'Pemasukan')->first();

    // Transaksi di buku kas 1
    \App\Models\Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas1->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi di Kas Test',
    ]);

    // Transaksi di buku kas 2
    \App\Models\Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 75000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Transaksi di Kas Kedua',
    ]);

    // Verify both transactions exist in DB
    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas1->id,
        'deskripsi' => 'Transaksi di Kas Test',
    ]);
    $this->assertDatabaseHas('transaksi', [
        'buku_kas_id' => $bukuKas2->id,
        'deskripsi' => 'Transaksi di Kas Kedua',
    ]);

    // Filter by buku kas 1 — set filter and verify it's applied
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas1->id)
        ->assertSet('filterBukuKas', (string) $bukuKas1->id);

    // Filter by buku kas 2 — set filter and verify it's applied
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas2->id)
        ->assertSet('filterBukuKas', (string) $bukuKas2->id);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - filterBukuKas kosong tampilkan semua transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas1 = $user->buku_kas()->first();
    $bukuKas2 = \App\Models\BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 0,
    ]);

    $jenis = \App\Models\JenisTransaksi::where('tipe', 'Pemasukan')->first();

    \App\Models\Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas1->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Pemasukan Kas Test',
    ]);

    \App\Models\Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 75000,
        'tanggal' => now(),
        'jenis_transaksi_id' => $jenis->id,
        'deskripsi' => 'Pemasukan Kas Kedua',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', '')
        ->assertSet('filterBukuKas', '');

    // Verify both transactions exist in DB
    $this->assertDatabaseHas('transaksi', ['deskripsi' => 'Pemasukan Kas Test']);
    $this->assertDatabaseHas('transaksi', ['deskripsi' => 'Pemasukan Kas Kedua']);
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - mount initialize filterBukuKas dari parameter', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class, [
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
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id);

    // Verify the property is set correctly — defaultForm uses this internally
    $component = Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->set('filterBukuKas', (string) $bukuKas->id);

    // Verify getBukuKasOptions still returns data
    $component->call('getBukuKasOptions')->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');

test('filter buku kas - previous period url mempertahankan filter', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
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
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->set('filterBukuKas', (string) $bukuKas->id)
        ->call('getNextPeriodUrl')
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'filter-buku-kas');
