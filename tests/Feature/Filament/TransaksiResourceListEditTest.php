<?php

use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use Livewire\Livewire;

// ==================== TRANSAKSI RESOURCE - LIST & EDIT PAGE TESTS ====================

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
