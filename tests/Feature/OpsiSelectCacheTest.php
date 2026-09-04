<?php

use App\Models\BukuKas;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\Transaksi;
use App\Models\Voucher;
use App\Services\OpsiSelectCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::clear();
});

test('opsi select disimpan dalam cache selama tiga hari', function () {
    $jumlahQuery = 0;

    $pertama = OpsiSelectCache::ingat('contoh', function () use (&$jumlahQuery): array {
        $jumlahQuery++;

        return [1 => 'Satu'];
    });
    $kedua = OpsiSelectCache::ingat('contoh', function () use (&$jumlahQuery): array {
        $jumlahQuery++;

        return [2 => 'Dua'];
    });

    expect($pertama)->toBe([1 => 'Satu'])
        ->and($kedua)->toBe([1 => 'Satu'])
        ->and($jumlahQuery)->toBe(1);
});

test('perubahan entitas global membersihkan cache opsi select terkait', function (string $model, string $entitas) {
    OpsiSelectCache::ingat($entitas, fn (): array => [1 => 'Lama']);

    $model::factory()->create();

    $hasil = OpsiSelectCache::ingat($entitas, fn (): array => [2 => 'Baru']);

    expect($hasil)->toBe([2 => 'Baru']);
})->with([
    'paket langganan' => [PaketLangganan::class, 'paket-langganan'],
    'metode pembayaran' => [MetodePembayaran::class, 'metode-pembayaran'],
    'voucher' => [Voucher::class, 'voucher'],
]);

test('opsi transaksi memakai hasil terbaru setelah tambah ubah dan hapus', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    $awal = Transaksi::opsiBukuKasYangDapatDikelola();
    $bukuKas = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Proyek']);
    $setelahTambah = Transaksi::opsiBukuKasYangDapatDikelola();

    $bukuKas->update(['nama_buku' => 'Usaha']);
    $setelahUbah = Transaksi::opsiBukuKasYangDapatDikelola();

    $bukuKas->delete();
    $setelahHapus = Transaksi::opsiBukuKasYangDapatDikelola();

    expect($awal)->not->toHaveKey($bukuKas->id)
        ->and($setelahTambah[$bukuKas->id])->toBe('Proyek')
        ->and($setelahUbah[$bukuKas->id])->toBe('Usaha')
        ->and($setelahHapus)->not->toHaveKey($bukuKas->id);
});
