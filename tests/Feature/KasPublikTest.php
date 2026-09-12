<?php

use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('kas publik dapat dibuat tanpa email hanya viewer dan dikelola pemilik', function () {
    Notification::fake();
    $owner = createRegularUserWithBukuKas();
    $kas = $owner->buku_kas()->first();
    $component = Livewire::actingAs($owner)->test(ListShareBukus::class)
        ->mountAction('create')
        ->assertFormFieldExists('privilege', fn ($field): bool => $field->getOptions() === ['viewer' => 'Viewer'])
        ->fillForm(['buku_kas_id' => $kas->id, 'user_id' => null])
        ->callMountedAction()->assertHasNoActionErrors();

    $share = ShareBuku::where('buku_kas_id', $kas->id)->whereNull('user_id')->sole();
    expect($share->privilege)->toBe('viewer')
        ->and(strlen($share->public_token))->toBe(64)
        ->and($share->invited_by_user_id)->toBe($owner->id)
        ->and($share->urlPublik())->toBe(route('kas.publik', $share->public_token));
    $token = $share->public_token;
    $component->assertTableColumnStateSet('link_publik', $share->urlPublik(), $share)
        ->callTableAction('edit', $share, data: ['berlaku_sampai' => today()->addDays(3)->toDateString()])
        ->assertHasNoTableActionErrors();
    expect($share->fresh()->public_token)->toBe($token);
    $component->callAction('create', data: ['buku_kas_id' => $kas->id, 'user_id' => null])
        ->assertHasActionErrors(['user_id']);

    $outsider = createRegularUserWithBukuKas();
    Livewire::actingAs($outsider)->test(ListShareBukus::class)
        ->assertCanNotSeeTableRecords([$share])
        ->callAction('create', data: ['buku_kas_id' => $kas->id, 'user_id' => null])
        ->assertHasActionErrors(['buku_kas_id']);
    Livewire::actingAs($owner)->test(ListShareBukus::class)
        ->callTableAction('delete', $share)->assertHasNoTableActionErrors();
    $this->assertDatabaseMissing('share_buku', ['id' => $share->id]);
    Notification::assertNothingSent();
});

test('kas publik menyajikan transaksi dan ringkasan tanpa login dengan isolasi data', function () {
    $owner = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $owner->id, 'nama_buku' => 'Kas warga', 'saldo' => 80000]);
    $private = BukuKas::factory()->create(['user_id' => $owner->id, 'nama_buku' => 'Kas rahasia']);
    $wallet = Dompet::factory()->create(['user_id' => $owner->id, 'nama_dompet' => 'Dompet rahasia']);
    $share = ShareBuku::factory()->create(['buku_kas_id' => $kas->id, 'user_id' => null]);
    $base = ['user_id' => $owner->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $wallet->id, 'tanggal' => '2026-08-10 10:00:00', 'jenis' => 'Pemasukan', 'nominal' => 100000, 'deskripsi' => 'Iuran warga'];
    Transaksi::withoutEvents(function () use ($base, $private): void {
        Transaksi::factory()->create($base);
        Transaksi::factory()->create(array_replace($base, ['jenis' => 'Pengeluaran', 'nominal' => 20000, 'deskripsi' => '<script>alert(1)</script>']));
        Transaksi::factory()->create(array_replace($base, ['buku_kas_id' => $private->id, 'deskripsi' => 'Transaksi rahasia']));
        Transaksi::factory()->create(array_replace($base, ['tanggal' => '2026-07-10 10:00:00', 'deskripsi' => 'Bulan sebelumnya']));
    });

    $response = $this->get($share->urlPublik().'?bulan=2026-08&buku_kas_id='.$private->id);
    $response->assertOk()->assertViewIs('kas-publik')
        ->assertViewHas('pemasukan', fn ($value): bool => (int) $value === 100000)
        ->assertViewHas('pengeluaran', fn ($value): bool => (int) $value === 20000)
        ->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 2)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer');
    expect($response->getContent())->toContain('Iuran warga', '&lt;script&gt;')
        ->not->toContain('Transaksi rahasia', 'Kas rahasia', 'Dompet rahasia', $owner->email, '<script>alert(1)</script>', 'Bulan sebelumnya');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->assertGuest();

    foreach (['post', 'put', 'patch', 'delete'] as $method) {
        $this->{$method}($share->urlPublik(), ['nominal' => 1])->assertStatus(405);
    }
    $this->getJson($share->urlPublik().'?bulan=tidak-valid')->assertUnprocessable();
    expect($kas->fresh()->saldo)->toBe(80000);
});

test('kas publik menolak token privat terjadwal kedaluwarsa dan dicabut', function () {
    $this->travelTo(now()->startOfSecond());
    $share = ShareBuku::factory()->create(['user_id' => null, 'privilege' => 'editor', 'berlaku_mulai' => now()->addDay()]);
    expect($share->privilege)->toBe('viewer');
    $url = $share->urlPublik();
    $this->get($url)->assertNotFound();
    $share->update(['berlaku_mulai' => now(), 'berlaku_sampai' => now()->addDay()]);
    $this->get($url)->assertOk();
    $this->travelTo($share->berlaku_sampai);
    $this->get($url)->assertNotFound();
    $share->update(['berlaku_sampai' => null, 'privilege' => 'editor']);
    expect($share->fresh()->privilege)->toBe('viewer');
    $this->get($url)->assertOk();
    $share->delete();
    $this->get($url)->assertNotFound();
    $this->get(route('kas.publik', Str::random(64)))->assertNotFound();
    $this->get('/kas-publik/1')->assertNotFound();

    $private = ShareBuku::factory()->create();
    expect($private->urlPublik())->toBeNull()->and($private->public_token)->toBeNull();
    $token = Str::random(64);
    DB::table('share_buku')->where('id', $private->id)->update(['public_token' => $token]);
    $this->get(route('kas.publik', $token))->assertNotFound();
});
