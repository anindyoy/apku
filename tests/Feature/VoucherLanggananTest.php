<?php

use App\Enums\StatusLangganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use App\Services\BuatOrderLangganan;
use Illuminate\Validation\ValidationException;

test('voucher menyimpan banyak kode dan tanggal kedaluwarsa bersifat opsional', function () {
    $voucher = Voucher::factory()->create(['masa_aktif' => null]);
    $codes = VoucherCode::factory()->count(3)->create(['voucher_id' => $voucher->id]);

    expect($voucher->masihBerlaku())->toBeTrue()
        ->and($voucher->codes()->pluck('id')->all())->toEqualCanonicalizing($codes->pluck('id')->all());
})->group('voucher');

test('voucher berulang dapat dipakai berkali kali oleh user mana pun', function () {
    $voucher = Voucher::factory()->create([
        'jumlah_diskon' => 25,
        'dapat_dipakai_berulang' => true,
    ]);
    $code = VoucherCode::factory()->create(['voucher_id' => $voucher->id, 'code' => 'hemat25']);
    $paket = PaketLangganan::factory()->create(['harga' => 100001]);
    $metode = MetodePembayaran::factory()->create();
    $userPertama = User::factory()->create(['role' => 'user']);
    $userKedua = User::factory()->create(['role' => 'user']);

    $orderPertama = app(BuatOrderLangganan::class)->handle($userPertama, $paket, $metode, $code);
    $orderKedua = app(BuatOrderLangganan::class)->handle($userKedua, $paket, $metode, $code);

    expect($code->fresh()->code)->toBe('HEMAT25')
        ->and($orderPertama->kode_voucher)->toBe('HEMAT25')
        ->and($orderPertama->persentase_diskon)->toBe(25)
        ->and($orderPertama->nominal_diskon)->toBe(25000)
        ->and($orderPertama->total_pembayaran)->toBe(75001)
        ->and($orderKedua->total_pembayaran)->toBe(75001);
})->group('voucher');

test('kode voucher non berulang hanya dapat dipakai pada satu order aktif', function () {
    $voucher = Voucher::factory()->create(['dapat_dipakai_berulang' => false]);
    $code = VoucherCode::factory()->create(['voucher_id' => $voucher->id]);
    $paket = PaketLangganan::factory()->create();
    $metode = MetodePembayaran::factory()->create();
    $user = User::factory()->create(['role' => 'user']);

    $order = app(BuatOrderLangganan::class)->handle($user, $paket, $metode, $code);

    expect(fn () => app(BuatOrderLangganan::class)->handle($user, $paket, $metode, $code))
        ->toThrow(ValidationException::class);

    $order->update(['status' => StatusLangganan::Dibatalkan]);
    $orderPengganti = app(BuatOrderLangganan::class)->handle($user, $paket, $metode, $code);

    expect($orderPengganti->status)->toBe(StatusLangganan::MenungguPembayaran);
})->group('voucher');

test('voucher yang melewati tanggal kedaluwarsa ditolak', function () {
    $voucher = Voucher::factory()->create(['masa_aktif' => today()->subDay()]);
    $code = VoucherCode::factory()->create(['voucher_id' => $voucher->id]);

    expect(fn () => app(BuatOrderLangganan::class)->handle(
        User::factory()->create(['role' => 'user']),
        PaketLangganan::factory()->create(),
        MetodePembayaran::factory()->create(),
        $code,
    ))->toThrow(ValidationException::class);
})->group('voucher');

test('resource voucher dan kode voucher hanya dapat diakses admin', function () {
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->get(route('filament.admin.resources.vouchers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('filament.admin.resources.voucher-codes.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('filament.admin.resources.vouchers.index'))->assertSuccessful();
    $this->actingAs($admin)->get(route('filament.admin.resources.voucher-codes.index'))->assertSuccessful();
})->group('voucher');
