<?php

use App\Enums\StatusLangganan;
use App\Filament\Resources\LanggananResource\Pages\ListLangganans;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Services\BuatOrderLangganan;
use App\Services\KonfirmasiPembayaranLangganan;
use App\Services\SetujuiLangganan;
use App\Services\TolakLangganan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('order menyimpan snapshot paket dan metode pembayaran', function () {
    $user = User::factory()->create(['role' => 'user', 'type' => 'reguler', 'masa_aktif' => null]);
    $paket = PaketLangganan::factory()->create(['label' => 'Premium 30 Hari', 'harga' => 25000, 'durasi_hari' => 30]);
    $metode = MetodePembayaran::factory()->create([
        'label' => 'BCA',
        'nama_penyedia' => 'Bank BCA',
        'nomor_tujuan' => '123456',
    ]);

    $order = app(BuatOrderLangganan::class)->handle($user, $paket, $metode);

    expect($order->kode_order)->toStartWith('LGN-')
        ->and($order->status)->toBe(StatusLangganan::MenungguPembayaran)
        ->and($order->label_paket)->toBe('Premium 30 Hari')
        ->and($order->harga)->toBe(25000)
        ->and($order->durasi_hari)->toBe(30)
        ->and($order->label_metode_pembayaran)->toBe('BCA')
        ->and($order->detail_pembayaran['nomor_tujuan'])->toBe('123456');

    $paket->update(['label' => 'Nama Baru', 'harga' => 90000]);
    $metode->update(['nomor_tujuan' => '999999']);

    expect($order->fresh()->label_paket)->toBe('Premium 30 Hari')
        ->and($order->fresh()->harga)->toBe(25000)
        ->and($order->fresh()->detail_pembayaran['nomor_tujuan'])->toBe('123456');
})->group('langganan');

test('paket dan metode pembayaran nonaktif tidak dapat digunakan', function () {
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['is_active' => false]);
    $metode = MetodePembayaran::factory()->create();

    expect(fn () => app(BuatOrderLangganan::class)->handle($user, $paket, $metode))
        ->toThrow(ValidationException::class);

    $paket->update(['is_active' => true]);
    $metode->update(['is_active' => false]);

    expect(fn () => app(BuatOrderLangganan::class)->handle($user, $paket, $metode))
        ->toThrow(ValidationException::class);
})->group('langganan');

test('konfirmasi pembayaran hanya dapat dilakukan pemilik order', function () {
    Notification::fake();
    $pemilik = User::factory()->create(['role' => 'user']);
    $userLain = User::factory()->create(['role' => 'user']);
    $order = Langganan::factory()->create(['user_id' => $pemilik->id]);

    expect(fn () => app(KonfirmasiPembayaranLangganan::class)->handle($userLain, $order, 'bukti/file.jpg'))
        ->toThrow(AuthorizationException::class);

    $hasil = app(KonfirmasiPembayaranLangganan::class)->handle($pemilik, $order, 'bukti/file.jpg', 'Sudah ditransfer');

    expect($hasil->status)->toBe(StatusLangganan::MenungguVerifikasi)
        ->and($hasil->bukti_pembayaran_path)->toBe('bukti/file.jpg')
        ->and($hasil->tanggal_konfirmasi)->not->toBeNull();
})->group('langganan');

test('persetujuan mengaktifkan premium dan tidak menghilangkan sisa masa aktif', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'role' => 'user',
        'type' => 'premium',
        'masa_aktif' => today()->addDays(10),
    ]);
    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
        'durasi_hari' => 30,
        'bukti_pembayaran_path' => 'bukti/file.jpg',
    ]);

    $hasil = app(SetujuiLangganan::class)->handle($admin, $order);

    expect($hasil->status)->toBe(StatusLangganan::Disetujui)
        ->and($hasil->masa_aktif_mulai->toDateString())->toBe(today()->addDays(11)->toDateString())
        ->and($hasil->masa_aktif_sampai->toDateString())->toBe(today()->addDays(40)->toDateString())
        ->and($hasil->diverifikasi_oleh)->toBe($admin->id)
        ->and($user->fresh()->type)->toBe('premium')
        ->and($user->fresh()->masa_aktif->toDateString())->toBe(today()->addDays(40)->toDateString());

    $persetujuanUlang = app(SetujuiLangganan::class)->handle($admin, $hasil);

    expect($persetujuanUlang->status)->toBe(StatusLangganan::Disetujui)
        ->and($user->fresh()->masa_aktif->toDateString())->toBe(today()->addDays(40)->toDateString());
})->group('langganan');

test('penolakan wajib melalui admin dan tidak mengubah masa aktif', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user', 'type' => 'reguler', 'masa_aktif' => null]);
    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
    ]);

    expect(fn () => app(TolakLangganan::class)->handle($user, $order, 'Tidak valid'))
        ->toThrow(AuthorizationException::class);

    $hasil = app(TolakLangganan::class)->handle($admin, $order, 'Nominal tidak sesuai');

    expect($hasil->status)->toBe(StatusLangganan::Ditolak)
        ->and($hasil->catatan_admin)->toBe('Nominal tidak sesuai')
        ->and($user->fresh()->masa_aktif)->toBeNull();
})->group('langganan');

test('riwayat user hanya menampilkan order miliknya sedangkan admin melihat semua', function () {
    $user = User::factory()->create(['role' => 'user']);
    $userLain = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);
    $orderSendiri = Langganan::factory()->create(['user_id' => $user->id]);
    $orderLain = Langganan::factory()->create(['user_id' => $userLain->id]);

    Livewire::actingAs($user)
        ->test(ListLangganans::class)
        ->assertCanSeeTableRecords([$orderSendiri])
        ->assertCanNotSeeTableRecords([$orderLain]);

    Livewire::actingAs($admin)
        ->test(ListLangganans::class)
        ->assertCanSeeTableRecords([$orderSendiri, $orderLain]);
})->group('langganan');

test('pengelolaan paket dan metode pembayaran hanya dapat diakses admin', function () {
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.paket-langganans.index'))
        ->assertForbidden();
    $this->actingAs($user)
        ->get(route('filament.admin.resources.metode-pembayarans.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.paket-langganans.index'))
        ->assertSuccessful();
    $this->actingAs($admin)
        ->get(route('filament.admin.resources.metode-pembayarans.index'))
        ->assertSuccessful();
})->group('langganan');

test('user dapat membuka halaman order sedangkan admin tidak dapat membuat order', function () {
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.langganans.create'))
        ->assertSuccessful();
    $this->actingAs($admin)
        ->get(route('filament.admin.resources.langganans.create'))
        ->assertForbidden();
})->group('langganan');
