<?php

use App\Filament\Pages\AkunSaya;
use App\Filament\Pages\Dashboard;
use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Services\PemeriksaanKeseimbanganSaldo;
use Livewire\Livewire;

test('pemeriksaan saldo default nonaktif dan dapat diatur per akun', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->pemeriksaanSaldoAktif())->toBeFalse();

    Livewire::actingAs($user)->test(AkunSaya::class)
        ->assertFormSet(['pemeriksaan_saldo_aktif' => false])
        ->fillForm(['penggunaan' => 'Pribadi/Keluarga', 'pemeriksaan_saldo_aktif' => true])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->fresh()->pemeriksaanSaldoAktif())->toBeTrue()
        ->and($other->fresh()->pemeriksaanSaldoAktif())->toBeFalse();
});

test('pemeriksaan menjumlahkan kas milik sendiri dan sumber dana satu kali', function () {
    $user = User::factory()->create(['hitung_tabungan_di_total' => false]);
    $other = User::factory()->create();
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 150000, 'is_tabungan' => false]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 50000, 'is_tabungan' => true]);
    BukuKas::factory()->create(['user_id' => $other->id, 'saldo' => 900000, 'is_tabungan' => false]);
    SumberDana::factory()->create(['user_id' => $user->id, 'saldo' => 100000]);
    SumberDana::factory()->create(['user_id' => $user->id, 'saldo' => 50000]);
    SumberDana::factory()->create(['user_id' => $other->id, 'saldo' => 900000]);

    expect(app(PemeriksaanKeseimbanganSaldo::class)->ringkasan($user))->toBe([
        'saldo_kas' => 150000,
        'saldo_sumber_dana' => 150000,
        'total_utang' => 0,
        'total_piutang' => 0,
        'harapan_sumber_dana' => 150000,
        'selisih' => 0,
        'seimbang' => true,
        'cakupan_kas' => 'kas biasa',
    ]);
});

test('dashboard menampilkan selisih dan tautan cocokkan saldo hanya saat fitur aktif', function () {
    $user = User::factory()->create(['pemeriksaan_saldo_aktif' => true]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 200000, 'is_tabungan' => false]);
    SumberDana::factory()->create(['user_id' => $user->id, 'saldo' => 150000]);

    Livewire::actingAs($user)->test(Dashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Total saldo kas dan sumber dana tidak cocok.')
        ->assertSeeText('Selengkapnya')
        ->assertSeeText('Detail Pemeriksaan Saldo')
        ->assertSeeText('Sumber dana yang diharapkan')
        ->assertSeeText('Rp 200.000')
        ->assertSeeText('Sumber dana aktual')
        ->assertSeeText('Rp 150.000')
        ->assertSeeText('Cocokkan Saldo');

    $user->forceFill(['pemeriksaan_saldo_aktif' => false])->save();
    Livewire::actingAs($user->fresh())->test(Dashboard::class)
        ->assertSuccessful()
        ->assertDontSeeText('Total saldo kas dan sumber dana tidak cocok.');
});

test('dashboard mengikuti cakupan kas tabungan pada pemeriksaan', function () {
    $user = User::factory()->create([
        'pemeriksaan_saldo_aktif' => true,
        'hitung_tabungan_di_total' => false,
    ]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 100000, 'is_tabungan' => false]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 70000, 'is_tabungan' => true]);
    SumberDana::factory()->create(['user_id' => $user->id, 'saldo' => 100000]);

    Livewire::actingAs($user)->test(Dashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Saldo kas dan sumber dana seimbang.')
        ->assertSeeText('Selengkapnya')
        ->assertSeeText('Detail Pemeriksaan Saldo')
        ->assertSeeText('Total kas biasa')
        ->assertSeeText('Rp 100.000');
});

test('pemeriksaan memasukkan utang dan piutang ke rumus pencocokan saldo', function () {
    $user = User::factory()->create(['hitung_tabungan_di_total' => false]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 100000, 'is_tabungan' => false]);
    SumberDana::factory()->create(['user_id' => $user->id, 'saldo' => 130000]);

    $utang = UtangPiutang::factory()->create(['user_id' => $user->id, 'tipe' => 'utang']);
    $utang->utang_piutang_detail()->create(['tipe' => 'tambah', 'nominal' => 50000]);

    $piutang = UtangPiutang::factory()->create(['user_id' => $user->id, 'tipe' => 'piutang']);
    $piutang->utang_piutang_detail()->create(['tipe' => 'tambah', 'nominal' => 20000]);

    expect(app(PemeriksaanKeseimbanganSaldo::class)->ringkasan($user))->toMatchArray([
        'saldo_kas' => 100000,
        'saldo_sumber_dana' => 130000,
        'total_utang' => 50000,
        'total_piutang' => 20000,
        'harapan_sumber_dana' => 130000,
        'selisih' => 0,
        'seimbang' => true,
        'cakupan_kas' => 'kas biasa',
    ]);
});
