<?php

use App\Filament\Pages\Dashboard;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Services\DashboardCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('cache dashboard bertahan tiga puluh menit dan terpisah per pengguna', function () {
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    Cache::flush();
    $calls = 0;
    $load = function () use (&$calls) {
        return ++$calls;
    };
    expect(DashboardCache::remember('kas', 101, $load))->toBe(1);
    $this->travel(29)->minutes();
    expect(DashboardCache::remember('kas', 101, $load))->toBe(1)
        ->and(DashboardCache::remember('kas', 102, $load))->toBe(2);
    $this->travel(2)->minutes();
    expect(DashboardCache::remember('kas', 101, $load))->toBe(3);
});

test('cache dashboard dibatalkan oleh penulisan model dan query langsung', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    $page = new Dashboard;
    expect($page->sectionData('kas'))->toHaveCount(0);
    $kas = BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 100]);
    expect((float) $page->sectionData('kas')->sole()->saldo)->toBe(100.0);
    $kas->update(['saldo' => 200]);
    expect((float) $page->sectionData('kas')->sole()->saldo)->toBe(200.0);
    BukuKas::whereKey($kas->id)->increment('saldo', 50);
    expect((float) $page->sectionData('kas')->sole()->saldo)->toBe(250.0);
    $kas->delete();
    expect($page->sectionData('kas'))->toHaveCount(0);
});

test('cache dashboard menginvalidasi dependensi data termasuk penulisan tanpa event', function () {
    $user = User::factory()->create();
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    foreach (['settings', 'langganan', 'transaksi', 'admin'] as $section) {
        DashboardCache::remember($section, $user->id, fn () => 'lama');
    }
    User::withoutEvents(fn () => $user->update(['name' => 'Nama terbaru']));
    foreach (['settings', 'langganan', 'transaksi', 'admin'] as $section) {
        expect(DashboardCache::remember($section, $user->id, fn () => 'baru'))->toBe('baru');
    }
    DashboardCache::remember('dompet', $user->id, fn () => 'tetap');
    $user->update(['name' => 'Nama berikutnya']);
    expect(DashboardCache::remember('dompet', $user->id, fn () => 'berubah'))->toBe('tetap');
});

test('cache dashboard tidak menyimpan hasil yang belum commit', function () {
    $calls = 0;
    $load = function () use (&$calls) {
        return ++$calls;
    };
    expect(DB::transactionLevel())->toBeGreaterThan(0);
    expect(DashboardCache::remember('kas', 123, $load))->toBe(1)
        ->and(DashboardCache::remember('kas', 123, $load))->toBe(2);
});

test('cache dashboard transaksi digunakan ulang dan diperbarui setelah perubahan tanpa event', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id]);
    $record = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
        'tanggal' => now(), 'deskripsi' => 'Sebelum perubahan',
    ]));
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    $page = Livewire::actingAs($user)->test(Dashboard::class)->instance();
    expect($page->getTableRecords()->sole()->deskripsi)->toBe('Sebelum perubahan');
    DB::connection()->enableQueryLog();
    DB::connection()->flushQueryLog();
    $page->getTableRecords();
    $page->getTableRecords();
    expect(DB::connection()->getQueryLog())->toBe([]);
    DB::connection()->disableQueryLog();
    Transaksi::withoutEvents(fn () => $record->update(['deskripsi' => 'Setelah perubahan']));
    expect($page->getTableRecords()->sole()->deskripsi)->toBe('Setelah perubahan');
});

test('cache dashboard utang diperbarui setelah pembayaran dan dompet setelah pemulihan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $utang = UtangPiutang::factory()->create(['user_id' => $user->id, 'tipe' => 'utang']);
    $utang->utang_piutang_detail()->create(['tipe' => 'tambah', 'nominal' => 100000]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id]);
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    $page = new Dashboard;
    expect((float) $page->sectionData('utang')['total'])->toBe(100000.0);
    $utang->utang_piutang_detail()->create(['tipe' => 'kurang', 'nominal' => 30000]);
    expect((float) $page->sectionData('utang')['total'])->toBe(70000.0);
    expect($page->sectionData('dompet'))->toHaveCount(1);
    $dompet->delete();
    expect($page->sectionData('dompet'))->toHaveCount(0);
    $dompet->restore();
    expect($page->sectionData('dompet'))->toHaveCount(1);
});
