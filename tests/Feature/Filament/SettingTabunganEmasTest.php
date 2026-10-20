<?php

use App\Filament\Clusters\Pengaturan;
use App\Filament\Pages\AkunSaya;
use App\Filament\Pages\GeneralSetting;
use App\Filament\Pages\Setting;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\TabunganEmasResource;
use App\Models\ApplicationSetting;
use App\Models\BukuKas;
use App\Models\TabunganEmas;
use App\Models\User;
use App\Services\PengaturanTabunganEmas;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('pengaturan tabungan emas default inactive pada user baru dan service global', function () {
    $service = app(PengaturanTabunganEmas::class);
    $user = User::factory()->create();

    expect($user->tabunganEmasAktif())->toBeFalse()
        ->and($user->tabungan_emas_aktif)->toBeFalse()
        ->and($service->aktif($user))->toBeFalse()
        ->and($service->semua())->toBe(['aktif' => false]);
});

test('admin dapat mengaktifkan dan menonaktifkan fitur tabungan emas lewat general setting', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanTabunganEmas::class);

    $this->actingAs($admin)->get(GeneralSetting::getUrl())->assertOk();
    $page = Livewire::actingAs($admin)->test(GeneralSetting::class)->assertSuccessful();

    // Pastikan nilai awal di form adalah false
    $page->assertFormSet(['tabungan_emas_aktif' => false]);

    // Aktifkan fitur
    $page->fillForm(['tabungan_emas_aktif' => true])->call('simpan')->assertHasNoFormErrors();
    expect($service->aktif($admin))->toBeTrue()
        ->and(ApplicationSetting::find('tabungan_emas')?->value)->toBe(['aktif' => true]);

    // Matikan kembali fitur
    $page->fillForm(['tabungan_emas_aktif' => false])->call('simpan')->assertHasNoFormErrors();
    expect($service->aktif($admin))->toBeFalse()
        ->and(ApplicationSetting::find('tabungan_emas')?->value)->toBe(['aktif' => false]);
});

test('admin dapat mengaktifkan dan menonaktifkan fitur tabungan emas lewat setting harga emas', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanTabunganEmas::class);

    $this->actingAs($admin)->get(Setting::getUrl())->assertOk();
    $page = Livewire::actingAs($admin)->test(Setting::class)->assertSuccessful();

    // Pastikan nilai awal di form adalah false
    $page->assertFormSet(['tabungan_emas_aktif' => false]);

    // Aktifkan fitur
    $page->fillForm(['tabungan_emas_aktif' => true])->call('simpan')->assertHasNoFormErrors();
    expect($service->aktif($admin))->toBeTrue()
        ->and(ApplicationSetting::find('tabungan_emas')?->value)->toBe(['aktif' => true]);
});

test('pengguna biasa tidak dapat menyimpan pengaturan tabungan emas global', function () {
    $user = createRegularUserWithBukuKas();
    expect(fn () => app(PengaturanTabunganEmas::class)->simpan($user, ['aktif' => true]))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('user bukan admin dapat mengaktifkan dan menonaktifkan fitur tabungan emas sendiri di halaman akun saya', function () {
    $user = createRegularUserWithBukuKas();
    expect($user->tabunganEmasAktif())->toBeFalse();

    // Buka halaman Akun Saya
    $page = Livewire::actingAs($user)->test(AkunSaya::class)->assertSuccessful();
    $page->assertFormSet(['tabungan_emas_aktif' => false]);

    // Aktifkan tabungan emas
    $page->fillForm(['penggunaan' => 'Pribadi/Keluarga', 'tabungan_emas_aktif' => true])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->fresh()->tabunganEmasAktif())->toBeTrue();

    // Matikan kembali tabungan emas
    $page->fillForm(['penggunaan' => 'Pribadi/Keluarga', 'tabungan_emas_aktif' => false])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect($user->fresh()->tabunganEmasAktif())->toBeFalse();
});

test('aktivasi fitur tabungan emas bersifat independen per user', function () {
    $userA = createRegularUserWithBukuKas();
    $userB = createRegularUserWithBukuKas();

    app(PengaturanTabunganEmas::class)->simpanUser($userA, true);

    expect($userA->fresh()->tabunganEmasAktif())->toBeTrue()
        ->and(app(PengaturanTabunganEmas::class)->aktif($userA))->toBeTrue()
        ->and($userB->fresh()->tabunganEmasAktif())->toBeFalse()
        ->and(app(PengaturanTabunganEmas::class)->aktif($userB))->toBeFalse();
});

test('fitur tabungan emas nonaktif menyembunyikan navigasi dan memblokir akses resource untuk user tersebut', function () {
    $user = createRegularUserWithBukuKas();
    $user->forceFill(['tabungan_emas_aktif' => false])->save();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect(TabunganEmasResource::canAccess())->toBeFalse()
        ->and(TabunganEmasResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(TabunganEmasResource::getUrl())->assertForbidden();

    $menus = app(Pengaturan::class)->getCachedSubNavigation();
    $labels = collect($menus)->flatMap(fn ($group) => $group->getItems())->map(fn ($item) => $item->getLabel())->all();
    expect($labels)->not->toContain('Tabungan Emas');
});

test('fitur tabungan emas aktif mengizinkan akses dan menampilkan navigasi untuk user tersebut', function () {
    $user = createRegularUserWithBukuKas();
    $user->forceFill(['tabungan_emas_aktif' => true])->save();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect(TabunganEmasResource::canAccess())->toBeTrue()
        ->and(TabunganEmasResource::shouldRegisterNavigation())->toBeTrue();

    $this->get(TabunganEmasResource::getUrl())->assertOk();

    $menus = app(Pengaturan::class)->getCachedSubNavigation();
    $labels = collect($menus)->flatMap(fn ($group) => $group->getItems())->map(fn ($item) => $item->getLabel())->all();
    expect($labels)->toContain('Tabungan Emas');
});

test('aksi dan indikator tabungan emas pada buku kas mengikuti status aktivasi fitur user', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    TabunganEmas::factory()->create(['buku_kas_id' => $kas->id, 'berat_gram' => 1.5]);

    // Saat user nonaktif
    $user->forceFill(['tabungan_emas_aktif' => false])->save();
    $page = Livewire::actingAs($user)->test(ListBukuKas::class)->assertSuccessful();
    $page->assertTableActionHidden('cekNilaiEmas', $kas)
        ->assertTableActionHidden('hargaEmasManual', $kas);
    $recordNonaktif = $page->instance()->getTableRecords()->firstWhere('id', $kas->id);
    expect($page->instance()->getTable()->getColumn('saldo')->record($recordNonaktif)->getDescriptionBelow())
        ->toBe('Jumlah transaksi: 0');

    // Saat user aktif
    $user->forceFill(['tabungan_emas_aktif' => true])->save();
    $pageAktif = Livewire::actingAs($user)->test(ListBukuKas::class)->assertSuccessful();
    $pageAktif->assertTableActionVisible('cekNilaiEmas', $kas)
        ->assertTableActionVisible('hargaEmasManual', $kas);
    $recordAktif = $pageAktif->instance()->getTableRecords()->firstWhere('id', $kas->id);
    expect($pageAktif->instance()->getTable()->getColumn('saldo')->record($recordAktif)->getDescriptionBelow())
        ->toBe('Jumlah transaksi: 0 · Produk emas: 1');
});
