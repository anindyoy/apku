<?php

use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;
use App\Models\BukuKas;
use App\Models\SumberDana;
use Livewire\Livewire;

test('kas overview menampilkan saldo kas terpilih saat halaman transaksi difilter buku kas', function () {
    $user = createRegularUserWithBukuKas();
    $kasUtama = $user->buku_kas()->firstOrFail();
    $kasUtama->update(['saldo' => 100000]);

    $kasKedua = BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Kedua',
        'saldo' => 18000,
    ]);

    $response = $this->actingAs($user)
        ->get(ListTransaksis::getUrl(['filter_buku_kas' => $kasKedua->id]));

    $response->assertSuccessful();

    expect($response->getContent())
        ->toContain('Rp 18,000')
        ->toContain('Semua Kas Rp 118,000');
})->group('filament', 'transaksi', 'kas-overview');

test('kas overview menampilkan saldo sumber dana terpilih saat filter kas dan sumber dana aktif', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    $kas->update(['saldo' => 18000]);

    $sumberDana = SumberDana::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 368000,
    ]);

    $response = $this->actingAs($user)
        ->get(ListTransaksis::getUrl([
            'filter_buku_kas' => $kas->id,
            'filter_dompet' => $sumberDana->id,
        ]));

    $response->assertSuccessful();

    expect($response->getContent())
        ->toContain('Rp 368,000')
        ->toContain('Saldo sumber dana terpilih');
})->group('filament', 'transaksi', 'kas-overview');

test('kas overview mempertahankan nilai filter setelah refresh livewire', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    $kas->update(['saldo' => 18000]);

    $sumberDana = SumberDana::factory()->create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 368000,
    ]);

    $widget = Livewire::actingAs($user)->test(KasOverview::class, [
        'filterBukuKas' => (string) $kas->id,
        'filterDompet' => (string) $sumberDana->id,
    ]);

    $widget->assertSuccessful();

    expect($widget->html())
        ->toContain('Rp 368,000')
        ->toContain('Saldo sumber dana terpilih');

    // Simulasikan pembaruan komponen Livewire (misalnya polling) yang tidak membawa query string URL.
    $widget->call('$refresh')->assertSuccessful();

    expect($widget->html())
        ->toContain('Rp 368,000')
        ->toContain('Saldo sumber dana terpilih');
})->group('filament', 'transaksi', 'kas-overview');
