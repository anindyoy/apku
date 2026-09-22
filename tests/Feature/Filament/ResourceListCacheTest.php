<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\ShareBuku;
use App\Models\TabunganEmas;
use App\Services\ResourceListCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('cache list bertahan tiga hari dan terpisah menurut pengguna serta pencarian', function () {
    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    Cache::flush();

    $calls = 0;
    $load = function () use (&$calls): int {
        return ++$calls;
    };

    expect(ResourceListCache::remember('kas', 1, ['search' => null], $load))->toBe(1);
    $this->travel(2)->days();
    expect(ResourceListCache::remember('kas', 1, ['search' => null], $load))->toBe(1)
        ->and(ResourceListCache::remember('kas', 2, ['search' => null], $load))->toBe(2)
        ->and(ResourceListCache::remember('kas', 1, ['search' => 'liburan'], $load))->toBe(3);
    $this->travel(2)->days();
    expect(ResourceListCache::remember('kas', 1, ['search' => null], $load))->toBe(4);
});

test('list kas dan dompet memakai data cache lalu menyegarkan tambah ubah hapus', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);
    $kas = $user->buku_kas()->firstOrFail();
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'nama_dompet' => 'Tunai']);

    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    Cache::flush();

    $kasRecords = fn () => Livewire::actingAs($user)->test(ListBukuKas::class)->instance()->getTableRecords();
    $dompetRecords = fn () => Livewire::actingAs($user)->test(ListDompet::class)->instance()->getTableRecords();

    expect($kasRecords()->firstWhere('id', $kas->id)->nama_buku)->toBe($kas->nama_buku)
        ->and($dompetRecords()->firstWhere('id', $dompet->id)->nama_dompet)->toBe($dompet->nama_dompet);

    $kasBaru = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Liburan']);
    $dompetBaru = Dompet::factory()->create(['user_id' => $user->id, 'nama_dompet' => 'Rekening']);
    expect($kasRecords()->pluck('id'))->toContain($kasBaru->id)
        ->and($dompetRecords()->pluck('id'))->toContain($dompetBaru->id);

    $kasBaru->update(['nama_buku' => 'Wisata']);
    $dompetBaru->update(['nama_dompet' => 'Tabungan']);
    expect($kasRecords()->firstWhere('id', $kasBaru->id)->nama_buku)->toBe('Wisata')
        ->and($dompetRecords()->firstWhere('id', $dompetBaru->id)->nama_dompet)->toBe('Tabungan');

    $kasBaru->delete();
    $dompetBaru->delete();
    expect($kasRecords()->pluck('id'))->not->toContain($kasBaru->id)
        ->and($dompetRecords()->pluck('id'))->not->toContain($dompetBaru->id);
});

test('cache list diperbarui saat saldo dan jumlah produk emas berubah', function () {
    $user = createRegularUserWithBukuKas();
    $this->actingAs($user);
    $kas = $user->buku_kas()->firstOrFail();
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'saldo' => 100]);

    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    Cache::flush();

    $kasRecords = fn () => Livewire::actingAs($user)->test(ListBukuKas::class)->instance()->getTableRecords();
    $dompetRecords = fn () => Livewire::actingAs($user)->test(ListDompet::class)->instance()->getTableRecords();
    expect($kasRecords()->firstWhere('id', $kas->id)->tabungan_emas_count)->toBe(0)
        ->and((int) $dompetRecords()->firstWhere('id', $dompet->id)->saldo)->toBe(100);

    TabunganEmas::factory()->create(['buku_kas_id' => $kas->id]);
    BukuKas::whereKey($kas->id)->increment('saldo', 50);
    Dompet::whereKey($dompet->id)->increment('saldo', 50);

    expect($kasRecords()->firstWhere('id', $kas->id)->tabungan_emas_count)->toBe(1)
        ->and((int) $kasRecords()->firstWhere('id', $kas->id)->saldo)->toBe(100050)
        ->and((int) $dompetRecords()->firstWhere('id', $dompet->id)->saldo)->toBe(150);
});

test('cache kas berakhir saat akses bersama mulai dan berakhir', function () {
    $owner = createRegularUserWithBukuKas();
    $member = createRegularUserWithBukuKas();
    $kasBersama = $owner->buku_kas()->firstOrFail();
    ShareBuku::create([
        'buku_kas_id' => $kasBersama->id,
        'user_id' => $member->id,
        'invited_by_user_id' => $owner->id,
        'privilege' => 'viewer',
        'berlaku_mulai' => now()->addHour(),
        'berlaku_sampai' => now()->addHours(2),
    ]);

    $database = Mockery::mock(DB::getFacadeRoot());
    $database->shouldReceive('transactionLevel')->andReturn(0);
    DB::swap($database);
    Cache::flush();

    $records = fn () => Livewire::actingAs($member)->test(ListBukuKas::class)->instance()->getTableRecords();
    expect($records()->pluck('id'))->not->toContain($kasBersama->id);

    $this->travel(61)->minutes();
    expect($records()->pluck('id'))->toContain($kasBersama->id);

    $this->travel(60)->minutes();
    expect($records()->pluck('id'))->not->toContain($kasBersama->id);
});
