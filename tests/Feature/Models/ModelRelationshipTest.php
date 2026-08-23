<?php

use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;

// ==================== USER MODEL ====================

test('user model - isSuper return true untuk super user', function () {
    $superUser = createSuperUser();
    $this->assertTrue($superUser->isSuper());
})
    ->group('models', 'relationships');

test('user model - isSuper return false untuk regular user', function () {
    $user = createRegularUserWithBukuKas();
    $this->assertFalse($user->isSuper());
})
    ->group('models', 'relationships');

test('user model - relasi buku_kas', function () {
    $user = createRegularUserWithBukuKas();
    $this->assertNotNull($user->buku_kas);
    $this->assertInstanceOf(BukuKas::class, $user->buku_kas()->first());
})
    ->group('models', 'relationships');

test('user model - relasi transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
    ]);

    $this->assertGreaterThan(0, $user->transaksi()->count());
})
    ->group('models', 'relationships');

test('user model - relasi utang_piutang', function () {
    $user = createRegularUserWithBukuKas();

    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    $this->assertGreaterThan(0, $user->utang_piutang()->count());
})
    ->group('models', 'relationships');

// ==================== BUKU KAS MODEL ====================

test('buku kas model - relasi user', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $this->assertNotNull($bukuKas->user);
    $this->assertEquals($user->id, $bukuKas->user->id);
})
    ->group('models', 'relationships');

test('buku kas model - relasi transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
    ]);

    $this->assertGreaterThan(0, $bukuKas->transaksi()->count());
})
    ->group('models', 'relationships');

// ==================== TRANSAKSI MODEL ====================

test('transaksi model - relasi user', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
    ]);

    $this->assertNotNull($transaksi->user);
    $this->assertEquals($user->id, $transaksi->user->id);
})
    ->group('models', 'relationships');

test('transaksi model - relasi buku_kas', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
    ]);

    $this->assertNotNull($transaksi->buku_kas);
    $this->assertEquals($bukuKas->id, $transaksi->buku_kas->id);
})
    ->group('models', 'relationships');

test('transaksi model - form method mengembalikan array', function () {
    $form = Transaksi::form();
    $this->assertIsArray($form);
    $this->assertNotEmpty($form);
})
    ->group('models', 'relationships');

test('transaksi model - form transfer method mengembalikan array', function () {
    $form = Transaksi::form(true);
    $this->assertIsArray($form);
    $this->assertNotEmpty($form);
})
    ->group('models', 'relationships');

// ==================== UTANG PIUTANG MODEL ====================

test('utang piutang model - relasi user', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    $this->assertNotNull($utangPiutang->user);
    $this->assertEquals($user->id, $utangPiutang->user->id);
})
    ->group('models', 'relationships');

test('utang piutang model - relasi utang_piutang_detail', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    $this->assertGreaterThan(0, $utangPiutang->utang_piutang_detail()->count());
})
    ->group('models', 'relationships');

test('utang piutang model - stat method mengembalikan array', function () {
    $user = createRegularUserWithBukuKas();

    $data = UtangPiutang::where('user_id', $user->id)->get();

    $stat = UtangPiutang::stat($data);
    $this->assertIsArray($stat);
    $this->assertNotEmpty($stat);
})
    ->group('models', 'relationships');

// ==================== UTANG PIUTANG DETAIL MODEL ====================

test('utang piutang detail model - scope tambah hanya return tipe tambah', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'kurang',
        'nominal' => 50000,
    ]);

    $tambah = UtangPiutangDetail::whereUtangPiutangId($utangPiutang->id)->tambah()->get();
    $this->assertCount(1, $tambah);
    $this->assertEquals('tambah', $tambah->first()->tipe);
})
    ->group('models', 'relationships');

test('utang piutang detail model - scope kurang hanya return tipe kurang', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'kurang',
        'nominal' => 50000,
    ]);

    $kurang = UtangPiutangDetail::whereUtangPiutangId($utangPiutang->id)->kurang()->get();
    $this->assertCount(1, $kurang);
    $this->assertEquals('kurang', $kurang->first()->tipe);
})
    ->group('models', 'relationships');

test('utang piutang detail model - relasi utang_piutang', function () {
    $user = createRegularUserWithBukuKas();

    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => 'utang',
    ]);

    $detail = UtangPiutangDetail::create([
        'utang_piutang_id' => $utangPiutang->id,
        'tipe' => 'tambah',
        'nominal' => 100000,
    ]);

    $this->assertNotNull($detail->utang_piutang);
    $this->assertEquals($utangPiutang->id, $detail->utang_piutang->id);
})
    ->group('models', 'relationships');
