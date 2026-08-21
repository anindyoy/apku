<?php

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
    ]);

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSee('Pemasukan');
})
    ->group('filament', 'transaksi');

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
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi->id])
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
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi->id])
        ->callTableAction('delete', $transaksi->id);

    $this->assertEmpty(DB::table('transaksi')->find($transaksi->id));
})
    ->group('filament', 'transaksi');
