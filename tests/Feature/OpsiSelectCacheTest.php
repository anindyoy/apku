<?php

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Voucher;
use App\Services\KategoriService;
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

test('opsi buku kas default, milik sendiri, dan transfer menggunakan cache dan diperbarui saat data berubah', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    $defaultAwal = Transaksi::opsiBukuKasDefault();
    $milikSendiriAwal = Transaksi::opsiBukuKasMilikSendiri();
    $transferAwal = Transaksi::opsiBukuKasTransfer();

    $bukuKas = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Tabungan Liburan']);

    $defaultSetelahTambah = Transaksi::opsiBukuKasDefault();
    $milikSendiriSetelahTambah = Transaksi::opsiBukuKasMilikSendiri();

    expect($defaultAwal)->not->toHaveKey($bukuKas->id)
        ->and($milikSendiriAwal)->not->toHaveKey($bukuKas->id)
        ->and($defaultSetelahTambah[$bukuKas->id])->toBe('Tabungan Liburan')
        ->and($milikSendiriSetelahTambah[$bukuKas->id])->toBe('Tabungan Liburan');

    $bukuKas->update(['nama_buku' => 'Tabungan Hari Tua']);

    expect(Transaksi::opsiBukuKasDefault()[$bukuKas->id])->toBe('Tabungan Hari Tua')
        ->and(Transaksi::opsiBukuKasMilikSendiri()[$bukuKas->id])->toBe('Tabungan Hari Tua');

    $bukuKas->delete();

    expect(Transaksi::opsiBukuKasDefault())->not->toHaveKey($bukuKas->id)
        ->and(Transaksi::opsiBukuKasMilikSendiri())->not->toHaveKey($bukuKas->id);
});

test('opsi sumber dana menggunakan cache dan pembersihan mencakup alias dompet', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    $awal = Transaksi::opsiSumberDanaSumberTransfer();
    $sumber = SumberDana::factory()->create(['user_id' => $user->id, 'nama_dompet' => 'Bank Mandiri']);

    $setelahTambah = Transaksi::opsiSumberDanaSumberTransfer();

    expect($awal)->not->toHaveKey($sumber->id)
        ->and($setelahTambah[$sumber->id])->toBe('Bank Mandiri');

    $sumber->update(['nama_dompet' => 'BSI Syariah']);
    expect(Transaksi::opsiSumberDanaSumberTransfer()[$sumber->id])->toBe('BSI Syariah');

    $sumber->delete();
    expect(Transaksi::opsiSumberDanaSumberTransfer())->not->toHaveKey($sumber->id);
});

test('opsi kategori menggunakan cache per kas dan dibersihkan saat kategori berubah', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);
    $kas = $user->buku_kas()->first();

    $awal = Transaksi::opsiKategori($kas->id);

    $kategori = app(KategoriService::class)->buat($user, [
        'nama' => 'Makan Siang',
        'tipe' => 'Pengeluaran',
    ], [$kas->id]);

    $setelahTambah = Transaksi::opsiKategori($kas->id);

    expect($awal)->not->toHaveKey($kategori->id)
        ->and($setelahTambah[$kategori->id])->toBe('Makan Siang');

    app(KategoriService::class)->ubah($user, $kategori, ['nama' => 'Makan Malam']);
    expect(Transaksi::opsiKategori($kas->id)[$kategori->id])->toBe('Makan Malam');

    app(KategoriService::class)->hapus($user, $kategori);
    expect(Transaksi::opsiKategori($kas->id))->not->toHaveKey($kategori->id);
});

test('opsi kas kelola kategori menggunakan cache dan diperbarui saat kas berubah', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);

    $awal = BukuKas::opsiKasKelolaKategori($user);
    $kasBaru = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Kas Usaha Baru']);

    $setelahTambah = BukuKas::opsiKasKelolaKategori($user);

    expect($awal)->not->toHaveKey($kasBaru->id)
        ->and($setelahTambah[$kasBaru->id]['label'])->toBe('Kas Usaha Baru');

    $kasBaru->update(['nama_buku' => 'Kas Usaha Revisi']);
    expect(BukuKas::opsiKasKelolaKategori($user)[$kasBaru->id]['label'])->toBe('Kas Usaha Revisi');

    $kasBaru->delete();
    expect(BukuKas::opsiKasKelolaKategori($user))->not->toHaveKey($kasBaru->id);
});

