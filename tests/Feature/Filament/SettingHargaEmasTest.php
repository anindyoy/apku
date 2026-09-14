<?php

use App\Filament\Pages\Setting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\HargaEmasService;
use App\Services\PengaturanHargaEmas;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('setting harga emas memakai default menyimpan perubahan dan kembali ke default', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = app(PengaturanHargaEmas::class);
    $defaults = config('services.harga_emas');
    expect($service->semua())->toBe($defaults);

    $this->actingAs($admin)->get(Setting::getUrl())->assertOk();
    $page = Livewire::actingAs($admin)->test(Setting::class)->assertSuccessful();
    expect(Setting::canAccess())->toBeTrue();
    $overrides = ['url' => 'https://example.com/api', 'source' => 'https://example.com/harga', 'timeout' => 15, 'cache_hours' => 2];
    Cache::put($service->kunciCache($defaults), ['lama' => true], 3600);
    $page->fillForm($overrides)->call('simpan')->assertHasNoFormErrors();

    expect($service->semua())->toBe($overrides)
        ->and(Cache::has($service->kunciCache($defaults)))->toBeFalse()
        ->and(ApplicationSetting::find('harga_emas')->value)->toBe($overrides);
    Livewire::test(Setting::class)->assertFormSet($overrides);

    $page->fillForm(['url' => null, 'source' => null, 'timeout' => null, 'cache_hours' => null])
        ->call('simpan')->assertHasNoFormErrors();
    expect($service->semua())->toBe($defaults)
        ->and(ApplicationSetting::find('harga_emas')->value)->toBe([]);
});

test('setting harga emas menolak pengguna biasa termasuk saat hak admin dicabut', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user)->get(Setting::getUrl())->assertForbidden();
    Livewire::actingAs($user)->test(Setting::class)->assertForbidden();
    expect(Setting::canAccess())->toBeFalse();
    expect(fn () => app(PengaturanHargaEmas::class)->simpan($user, ['timeout' => 10]))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $admin = User::factory()->create(['role' => 'admin']);
    $page = Livewire::actingAs($admin)->test(Setting::class);
    $admin->update(['role' => 'user']);
    $page->call('simpan')->assertForbidden();
    expect(ApplicationSetting::find('harga_emas'))->toBeNull();
});

test('setting harga emas memvalidasi url dan batas angka', function ($data) {
    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(Setting::class)->fillForm($data)->call('simpan')
        ->assertHasFormErrors(['url', 'source', 'timeout', 'cache_hours']);
    expect(ApplicationSetting::find('harga_emas'))->toBeNull();
})->with([
    [['url' => 'ftp://example.com', 'source' => 'javascript:alert(1)', 'timeout' => 0, 'cache_hours' => 169]],
    [['url' => 'bukan-url', 'source' => 'bukan-url', 'timeout' => 61, 'cache_hours' => 1.5]],
]);

test('setting harga emas diterapkan pada api timeout dan masa cache', function () {
    Cache::flush();
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);
    $kas = $user->buku_kas()->first();
    $pengaturan = app(PengaturanHargaEmas::class);
    $pengaturan->simpan($admin, ['url' => 'https://example.com/emas', 'timeout' => 12, 'cache_hours' => 1]);
    expect($pengaturan->semua()['source'])->toBe(config('services.harga_emas.source'));
    $timeouts = [];
    Http::fake(function ($request, $options) use (&$timeouts) {
        $timeouts[] = $options['timeout'];

        return Http::response(['data' => [[
            'material' => 'gold', 'currency' => 'IDR', 'weight' => 1,
            'buybackPrice' => 1200000, 'displayName' => 'Penyedia uji', 'recordedDate' => '2026-09-14',
        ]]]);
    });

    $harga = app(HargaEmasService::class);
    expect($harga->hargaBuyback($kas)['harga_per_gram'])->toBe(1200000);
    $harga->hargaBuyback($kas);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/emas');
    expect($timeouts)->toBe([12]);

    $this->travel(61)->minutes();
    $harga->hargaBuyback($kas);
    Http::assertSentCount(2);
    $pengaturan->simpan($admin, ['url' => 'https://example.com/emas-baru', 'timeout' => 9, 'cache_hours' => 2]);
    $harga->hargaBuyback($kas);
    Http::assertSentCount(3);
    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/emas-baru');
    expect($timeouts)->toBe([12, 12, 9]);
    $this->travelBack();
});
