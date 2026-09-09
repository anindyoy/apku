<?php

use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

// Pengujian halaman daftar dan edit transaksi.

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
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp')
        ->assertSeeText('Test deskripsi transaksi');
})
    ->group('filament', 'transaksi');

test('transaksi resource - tombol ubah pada daftar membuka modal edit', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
        'tipe' => 'Pemasukan',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'deskripsi' => 'Transaksi yang akan diubah',
        'jenis_transaksi_id' => $jenis->id,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountTableAction('edit', $transaksi)
        ->assertSet('mountedActions.0.name', 'edit')
        ->assertSchemaStateSet([
            'nominal' => 100000,
            'deskripsi' => 'Transaksi yang akan diubah',
        ]);
})
    ->group('filament', 'transaksi', 'ubah-transaksi');

test('transaksi resource - tombol tambah membuka modal transaksi', function () {
    $user = createRegularUserWithBukuKas();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertActionVisible('Tambah transaksi')
        ->mountAction('Tambah transaksi')
        ->assertSet('mountedActions.0.name', 'Tambah transaksi')
        ->assertActionDataSet(['jenis_form' => 'pemasukan']);
})
    ->group('filament', 'transaksi', 'tambah-transaksi');

test('warna tombol submit mengikuti jenis transaksi yang dipilih', function () {
    $user = createRegularUserWithBukuKas();
    $komponen = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->mountAction('Tambah transaksi');

    foreach ([
        'pemasukan' => 'success',
        'pengeluaran' => 'danger',
        'transfer_kas' => 'info',
        'transfer_dompet' => 'warning',
    ] as $jenisForm => $warna) {
        $komponen->set('mountedActions.0.data.jenis_form', $jenisForm);

        expect($komponen->instance()->getMountedAction()?->getModalSubmitAction()?->getColor())
            ->toBe($warna);
    }
})
    ->group('filament', 'transaksi', 'tambah-transaksi', 'warna-submit');

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
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
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
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
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
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp')
        ->assertSeeText('Test pengeluaran');
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
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', null)
        ->call('save')
        ->assertHasErrors(['data.nominal']);
})
    ->group('filament', 'transaksi');

test('transaksi resource - list page kas overview widget ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful();
})
    ->group('filament', 'transaksi', 'widgets');
