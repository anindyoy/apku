<?php

use App\Models\BukuKas;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\TransaksiSeeder;
use Database\Seeders\UserTransactionSeeder;

test('transaksi factory selalu mengisi dompet milik pengguna', function () {
    $user = User::factory()->create(['role' => 'reguler']);
    $bukuKas = BukuKas::factory()->create(['user_id' => $user->id]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
    ]);

    expect($transaksi->dompet_id)->not->toBeNull()
        ->and($transaksi->dompet->user_id)->toBe($user->id);
});

test('user transaction seeder mengisi buku kas dan dompet yang valid', function () {
    $user = User::factory()->create(['role' => 'reguler']);

    (new UserTransactionSeeder)->setEmail($user->email)->setMonths(1)->run();

    $transaksi = Transaksi::withoutGlobalScopes()->where('user_id', $user->id)->get();

    expect($transaksi)->not->toBeEmpty()
        ->and($transaksi->every(fn (Transaksi $item): bool => filled($item->buku_kas_id) && filled($item->dompet_id)))->toBeTrue()
        ->and($user->buku_kas()->firstOrFail()->is_default)->toBeTrue()
        ->and($user->dompet()->firstOrFail()->is_default)->toBeTrue();
});

test('transaksi seeder menghasilkan relasi valid saldo konsisten dan kode transfer uuid', function () {
    $user = User::factory()->create(['role' => 'reguler']);
    JenisTransaksi::factory()->create(['user_id' => $user->id, 'tipe' => 'Pemasukan']);
    JenisTransaksi::factory()->create(['user_id' => $user->id, 'tipe' => 'Pengeluaran']);

    app(TransaksiSeeder::class)->run();

    $transaksi = Transaksi::withoutGlobalScopes()->where('user_id', $user->id)->get();
    $bukuKas = BukuKas::withoutGlobalScopes()->where('user_id', $user->id)->get();
    $dompet = $user->dompet()->firstOrFail();
    $saldoHistori = $transaksi->sum(fn (Transaksi $item): int => in_array($item->jenis, ['Pemasukan', 'Transfer Pemasukan'], true)
        ? $item->nominal
        : -$item->nominal);

    expect($transaksi)->not->toBeEmpty()
        ->and($transaksi->every(fn (Transaksi $item): bool => filled($item->buku_kas_id) && filled($item->dompet_id)))->toBeTrue()
        ->and($bukuKas->where('is_default', true))->toHaveCount(1)
        ->and($dompet->saldo)->toBe($saldoHistori)
        ->and($transaksi->whereNotNull('transfer_code')->every(
            fn (Transaksi $item): bool => (bool) preg_match('/^[0-9a-f-]{36}$/i', $item->transfer_code)
        ))->toBeTrue();
});
