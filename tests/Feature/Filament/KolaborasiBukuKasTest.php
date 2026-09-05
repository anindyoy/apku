<?php

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;

test('kolaborasi buku kas memberi viewer akses baca tanpa akses tulis', function () {
    $pemilik = createRegularUserWithBukuKas();
    $viewer = createRegularUserWithBukuKas();
    $buku = $pemilik->buku_kas()->first();

    ShareBuku::create([
        'buku_kas_id' => $buku->id,
        'user_id' => $viewer->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'viewer',
        'berlaku_mulai' => now()->subMinute(),
    ]);

    $this->actingAs($viewer);
    Cache::clear();

    expect(BukuKas::find($buku->id))->not->toBeNull()
        ->and($viewer->dapatMengelolaTransaksiPada($buku))->toBeFalse();
})->group('filament', 'kolaborasi-buku-kas');

test('kolaborasi buku kas memungkinkan editor mencatat dengan dompet dan kategori sendiri', function () {
    $pemilik = createRegularUserWithBukuKas();
    $editor = createRegularUserWithBukuKas();
    $buku = $pemilik->buku_kas()->first();
    $dompet = Dompet::factory()->create(['user_id' => $editor->id, 'saldo' => 0]);
    $kategori = JenisTransaksi::factory()->create([
        'user_id' => $editor->id,
        'tipe' => 'Pemasukan',
    ]);

    ShareBuku::create([
        'buku_kas_id' => $buku->id,
        'user_id' => $editor->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->subMinute(),
    ]);

    $saldoAwal = $buku->saldo;
    $this->actingAs($editor);
    $transaksi = app(TransaksiService::class)->buat($editor, [
        'buku_kas_id' => $buku->id,
        'dompet_id' => $dompet->id,
        'jenis_transaksi_id' => $kategori->id,
        'nominal' => 50000,
        'tanggal' => now(),
    ], 'Pemasukan');

    expect($transaksi->user_id)->toBe($editor->id)
        ->and($buku->fresh()->saldo)->toBe($saldoAwal + 50000)
        ->and($dompet->fresh()->saldo)->toBe(50000);
})->group('filament', 'kolaborasi-buku-kas');

test('kolaborasi buku kas tidak membuka akses bagi pengguna lain atau share tidak aktif', function () {
    $pemilik = createRegularUserWithBukuKas();
    $pengguna = createRegularUserWithBukuKas();
    $buku = $pemilik->buku_kas()->first();

    ShareBuku::create([
        'buku_kas_id' => $buku->id,
        'user_id' => $pengguna->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->addDay(),
    ]);

    $this->actingAs($pengguna);

    expect(BukuKas::find($buku->id))->toBeNull()
        ->and(Transaksi::where('buku_kas_id', $buku->id)->exists())->toBeFalse();
})->group('filament', 'kolaborasi-buku-kas');

test('kolaborasi buku kas mencegah editor mengubah transaksi anggota lain', function () {
    $pemilik = createRegularUserWithBukuKas();
    $editor = createRegularUserWithBukuKas();
    $buku = $pemilik->buku_kas()->first();
    $dompetPemilik = Dompet::factory()->create(['user_id' => $pemilik->id]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $pemilik->id,
        'buku_kas_id' => $buku->id,
        'dompet_id' => $dompetPemilik->id,
        'jenis' => 'Pemasukan',
    ]);

    ShareBuku::create([
        'buku_kas_id' => $buku->id,
        'user_id' => $editor->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->subMinute(),
    ]);

    $this->actingAs($editor);

    expect(fn () => app(TransaksiService::class)->hapus($editor, $transaksi))
        ->toThrow(AuthorizationException::class);
})->group('filament', 'kolaborasi-buku-kas');

test('kolaborasi buku kas langsung berhenti setelah akses dicabut', function () {
    $pemilik = createRegularUserWithBukuKas();
    $editor = createRegularUserWithBukuKas();
    $buku = $pemilik->buku_kas()->first();
    $share = ShareBuku::create([
        'buku_kas_id' => $buku->id,
        'user_id' => $editor->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->subMinute(),
    ]);

    $share->delete();
    $this->actingAs($editor);

    expect(BukuKas::find($buku->id))->toBeNull()
        ->and($editor->hakAksesPada($buku))->toBeNull();
})->group('filament', 'kolaborasi-buku-kas');

test('kolaborasi buku kas menjadi hanya baca ketika buku pemilik terkena batas akun', function () {
    $pemilik = createRegularUserWithBukuKas();
    $pemilik->update(['masa_aktif' => today()->subDay()]);
    BukuKas::factory()->create(['user_id' => $pemilik->id, 'nama_buku' => 'Kas Gratis Kedua']);
    $bukuTerbatas = BukuKas::factory()->create(['user_id' => $pemilik->id, 'nama_buku' => 'Kas Premium']);
    $editor = createRegularUserWithBukuKas();

    ShareBuku::create([
        'buku_kas_id' => $bukuTerbatas->id,
        'user_id' => $editor->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->subMinute(),
    ]);

    $this->actingAs($editor);

    expect($editor->dapatMelihatBukuKas($bukuTerbatas))->toBeTrue()
        ->and($editor->dapatMengelolaTransaksiPada($bukuTerbatas))->toBeFalse();
})->group('filament', 'kolaborasi-buku-kas');
