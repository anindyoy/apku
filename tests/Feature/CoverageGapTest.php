<?php

use App\Filament\Resources\PiutangResource\Pages\EditPiutang;
use App\Filament\Resources\UtangResource\Pages\EditUtang;
use App\Filament\Widgets\UtangPiutangDetailOverview;
use App\Models\BukuKas;
use App\Models\ShareBuku;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

test('relasi share buku mengarah ke pengguna dan buku kas', function () {
    $pemilik = createRegularUserWithBukuKas();
    $pengguna = User::factory()->create(['role' => 'reguler']);
    $bukuKas = $pemilik->buku_kas()->firstOrFail();

    $shareBuku = ShareBuku::create([
        'user_id' => $pengguna->id,
        'buku_kas_id' => $bukuKas->id,
        'privilege' => 'viewer',
    ]);

    expect($shareBuku->user->is($pengguna))->toBeTrue()
        ->and($shareBuku->buku_kas->is($bukuKas))->toBeTrue();
})->group('coverage-gap');

test('buku kas acak hanya mengambil buku milik pengguna yang diminta', function () {
    $pengguna = createRegularUserWithBukuKas();
    $penggunaLain = createRegularUserWithBukuKas();

    $hasil = BukuKas::getRandomBukuKas($pengguna->id)->get();

    expect($hasil)->not->toBeEmpty()
        ->and($hasil->pluck('user_id')->unique()->all())->toBe([$pengguna->id])
        ->and($hasil->contains('user_id', $penggunaLain->id))->toBeFalse();
})->group('coverage-gap');

test('halaman edit utang dan piutang dapat dirender beserta aksi hapus', function (string $tipe, string $halaman) {
    $pengguna = createRegularUserWithBukuKas();
    $record = UtangPiutang::factory()->create([
        'user_id' => $pengguna->id,
        'tipe' => $tipe,
    ]);

    Livewire::actingAs($pengguna)
        ->test($halaman, ['record' => $record->id])
        ->assertSuccessful()
        ->assertActionExists('delete');
})->with([
    'utang' => ['utang', EditUtang::class],
    'piutang' => ['piutang', EditPiutang::class],
])->group('coverage-gap');

test('widget detail menghitung selisih nominal tambah dan kurang', function () {
    $pengguna = createRegularUserWithBukuKas();
    $record = UtangPiutang::factory()->create([
        'user_id' => $pengguna->id,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $record->id,
        'tipe' => 'tambah',
        'nominal' => 250000,
    ]);
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $record->id,
        'tipe' => 'kurang',
        'nominal' => 50000,
    ]);

    Livewire::actingAs($pengguna)
        ->test(UtangPiutangDetailOverview::class, ['record' => $record->id])
        ->assertSuccessful()
        ->assertSeeText('Rp 200,000');

    $method = new ReflectionMethod(UtangPiutangDetailOverview::class, 'getTablePage');

    expect($method->invoke(new UtangPiutangDetailOverview()))
        ->toBe(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class);
})->group('coverage-gap');
