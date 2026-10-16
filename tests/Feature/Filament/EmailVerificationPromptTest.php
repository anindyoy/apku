<?php

use App\Filament\Pages\Auth\EmailVerificationPrompt;
use App\Filament\Pages\GeneralSetting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\PengaturanKontak;
use Livewire\Livewire;

// ─── PengaturanKontak service ────────────────────────────────────────────────

test('pengaturan kontak mengembalikan nomor default dari migrasi jika belum diubah', function () {
    $service = app(PengaturanKontak::class);

    expect($service->nomorWa())->toBe('628567851359')
        ->and($service->urlWa())->toBe('https://wa.me/628567851359');
});

test('pengaturan kontak menyimpan dan mengembalikan nomor wa', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanKontak::class);

    $service->simpan($admin, ['wa' => '6281234567890']);

    expect($service->nomorWa())->toBe('6281234567890')
        ->and($service->urlWa())->toBe('https://wa.me/6281234567890')
        ->and($service->urlWa('Halo admin'))->toBe('https://wa.me/6281234567890?text=Halo%20admin')
        ->and(ApplicationSetting::find('kontak')->value)->toBe(['wa' => '6281234567890']);
});

test('pengaturan kontak menolak pengguna biasa', function () {
    $user = createRegularUserWithBukuKas();

    expect(fn () => app(PengaturanKontak::class)->simpan($user, ['wa' => '6281234567890']))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('pengaturan kontak memvalidasi format nomor wa', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanKontak::class);

    // Nomor terlalu pendek
    expect(fn () => $service->simpan($admin, ['wa' => '123']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    // Karakter non-angka
    expect(fn () => $service->simpan($admin, ['wa' => '+6281234567890']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('pengaturan kontak menghapus nomor ketika kosong dikirim', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanKontak::class);

    $service->simpan($admin, ['wa' => '6281234567890']);
    $service->simpan($admin, ['wa' => '']);

    expect($service->nomorWa())->toBeNull()
        ->and(ApplicationSetting::find('kontak')->value)->toBe([]);
});

// ─── GeneralSetting page – form kontak ──────────────────────────────────────

test('halaman general setting menampilkan dan menyimpan nomor wa admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(GeneralSetting::getUrl())->assertOk();

    Livewire::actingAs($admin)
        ->test(GeneralSetting::class)
        ->fillForm(['wa' => '628111222333'])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect(app(PengaturanKontak::class)->nomorWa())->toBe('628111222333');
});

test('halaman general setting menolak nomor wa tidak valid', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(GeneralSetting::class)
        ->fillForm(['wa' => 'bukan-nomor'])
        ->call('simpan')
        ->assertHasFormErrors(['wa']);
});

// ─── EmailVerificationPrompt ─────────────────────────────────────────────────

test('halaman verifikasi email menampilkan link kembali ke halaman utama', function () {
    $user = User::factory()->unverified()->create();

    $response = Livewire::actingAs($user)->test(EmailVerificationPrompt::class);

    // Memastikan komponen berhasil dirender tanpa error
    $response->assertSuccessful();
});

test('halaman verifikasi email menampilkan tombol wa dengan email user jika nomor diatur', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    app(PengaturanKontak::class)->simpan($admin, ['wa' => '6281234567890']);

    $user = User::factory()->unverified()->create(['email' => 'user.baru@example.com']);
    $response = Livewire::actingAs($user)->test(EmailVerificationPrompt::class);

    $response->assertSuccessful();
    $expectedUrl = app(PengaturanKontak::class)->urlWa('Halo Admin APKu, saya butuh bantuan verifikasi email akun saya (user.baru@example.com).');
    expect($response->html())->toContain(htmlentities($expectedUrl));
});

test('halaman verifikasi email tidak menampilkan tombol wa jika nomor dikosongkan', function () {
    ApplicationSetting::where('key', 'kontak')->delete();

    $user = User::factory()->unverified()->create();

    $response = Livewire::actingAs($user)->test(EmailVerificationPrompt::class);

    $response->assertSuccessful();
    expect(app(PengaturanKontak::class)->urlWa())->toBeNull();
});

test('aksi logout pada halaman verifikasi email mengeluarkan pengguna', function () {
    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test(EmailVerificationPrompt::class)
        ->callAction('logout');

    // Setelah logout, auth session tidak lagi berisi user ini
    $this->assertGuest();
});
