<?php

use App\Models\Transaksi;
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
        ->assertSee('100000');
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat mengedit nominal transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
    ]);

    $initialSaldo = $bukuKas->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi])
        ->assertSuccessful()
        ->set('data.nominal', 150000)
        ->call('save')
        ->assertHasNoErrors();

    $bukuKas->refresh();
    $this->assertEquals($initialSaldo + 50000, $bukuKas->saldo);
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat menghapus transaksi pemasukan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
    ]);

    $saldoBefore = $bukuKas->saldo;

    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi])
        ->callTableAction('delete', $transaksi);

    $bukuKas->refresh();

    $this->assertEquals($saldoBefore - $transaksi->nominal, $bukuKas->saldo);
    $this->assertSoftDeleted('transaksi', $transaksi->getAttributes());
})
    ->group('filament', 'transaksi');
