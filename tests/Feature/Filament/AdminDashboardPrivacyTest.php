<?php

use App\Enums\StatusLangganan;
use App\Filament\Pages\AkunSaya;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Kategori;
use App\Filament\Pages\Laporan;
use App\Filament\Pages\PencarianTransaksi;
use App\Filament\Resources\BukuKasResource;
use App\Filament\Resources\DompetResource;
use App\Filament\Resources\PiutangResource;
use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UtangResource;
use App\Filament\Widgets\AdminOverview;
use App\Models\Langganan;
use App\Models\User;
use Livewire\Livewire;

test('dashboard admin menjadi halaman utama dan menampilkan data agregat', function () {
    $admin = createAdminUser();
    User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addWeek()]);
    Langganan::factory()->create(['status' => StatusLangganan::MenungguVerifikasi]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(AdminOverview::class)
        ->assertSeeText('Pengguna')
        ->assertSeeText('Premium aktif')
        ->assertSeeText('Menunggu verifikasi');
});

test('menu data keuangan pribadi disembunyikan dari admin', function () {
    $admin = createAdminUser();
    $this->actingAs($admin);

    $menuPrivat = [
        TransaksiResource::class,
        PencarianTransaksi::class,
        Laporan::class,
        BukuKasResource::class,
        DompetResource::class,
        Kategori::class,
        UtangResource::class,
        PiutangResource::class,
        AkunSaya::class,
    ];

    foreach ($menuPrivat as $menu) {
        expect($menu::shouldRegisterNavigation())->toBeFalse();
    }

    expect(Dashboard::shouldRegisterNavigation())->toBeTrue();
});

test('menu keuangan pengguna reguler tetap tersedia', function () {
    $user = User::factory()->create(['role' => 'reguler']);
    $this->actingAs($user);

    expect(TransaksiResource::shouldRegisterNavigation())->toBeTrue()
        ->and(Laporan::shouldRegisterNavigation())->toBeTrue()
        ->and(Dashboard::shouldRegisterNavigation())->toBeFalse();
});

test('daftar pengguna tidak memuat metadata aktivitas keuangan', function () {
    $admin = createAdminUser();
    $this->actingAs($admin);

    $halaman = Livewire::actingAs($admin)->test(ListUsers::class);
    $namaKolom = array_keys($halaman->instance()->getTable()->getColumns());

    expect($namaKolom)
        ->not->toContain('buku_kas_count')
        ->not->toContain('transaksi_count')
        ->not->toContain('utang_piutang_count');
});
