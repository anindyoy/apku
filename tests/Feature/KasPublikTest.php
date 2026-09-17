<?php

use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\Process\Process;

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
    $component->assertTableColumnDoesNotExist('link_publik')
        ->assertTableColumnFormattedStateSet('berlaku_mulai', $share->berlaku_mulai->format('d M Y'), $share)
        ->callTableAction('edit', $share, data: ['berlaku_sampai' => today()->addDays(3)->toDateString()])
        ->assertHasNoTableActionErrors();
    expect($share->fresh()->public_token)->toBe($token);
    $component->assertTableColumnFormattedStateSet('berlaku_sampai', $share->fresh()->berlaku_sampai->format('d M Y'), $share->fresh());
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
    expect($response->getContent())->toContain('Iuran warga', '&lt;script&gt;', 'data-public-transaksi-mobile', 'public-mobile-amount')
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

test('kas publik tombol salin menyalin url lengkap dan memberi hasil sesuai clipboard', function () {
    $owner = createRegularUserWithBukuKas();
    $kas = $owner->buku_kas()->first();
    $public = ShareBuku::factory()->create(['buku_kas_id' => $kas->id, 'user_id' => null]);
    $private = ShareBuku::factory()->create(['buku_kas_id' => $kas->id]);
    $handler = null;

    Livewire::actingAs($owner)->test(ListShareBukus::class)
        ->assertTableActionVisible('salinLinkPublik', $public)
        ->assertTableActionHidden('salinLinkPublik', $private)
        ->assertTableActionExists('salinLinkPublik', function ($action) use (&$handler): bool {
            $handler = $action->getAlpineClickHandler();

            return $action->getLabel() === 'Salin link' && $action->isButton() && $action->getUrl() === null;
        }, $public);

    $script = <<<'JS'
        const assert = require('node:assert/strict');
        (async () => {
            for (const mode of ['success', 'denied', 'unavailable']) {
                const notifications = [];
                let copied;
                global.FilamentNotification = class {
                    title(value) { this.message = value; return this; }
                    body() { return this; }
                    success() { this.level = 'success'; return this; }
                    danger() { this.level = 'danger'; return this; }
                    send() { notifications.push({ level: this.level, message: this.message }); }
                };
                global.window = { navigator: { clipboard: mode === 'unavailable' ? undefined : {
                    writeText: async (value) => {
                        if (mode === 'denied') throw new Error('denied');
                        copied = value;
                    },
                } } };
                await eval(process.argv[1]);
                assert.equal(notifications.length, 1);
                assert.equal(notifications[0].level, mode === 'success' ? 'success' : 'danger');
                assert.equal(copied, mode === 'success' ? process.argv[2] : undefined);
            }
            process.stdout.write('ok');
        })().catch(error => { console.error(error); process.exitCode = 1; });
        JS;
    $process = new Process(['node', '-e', $script, $handler, $public->urlPublik()]);
    $process->run();
    expect($process->getExitCode())->toBe(0, $process->getErrorOutput())
        ->and($process->getOutput())->toBe('ok');
});

test('kas publik pencarian mencakup semua tanggal dengan batas kas dan mempertahankan paginasi', function () {
    $owner = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $owner->id]);
    $private = BukuKas::factory()->create(['user_id' => $owner->id]);
    $wallet = Dompet::factory()->create(['user_id' => $owner->id]);
    $category = JenisTransaksi::factory()->create(['user_id' => $owner->id, 'nama_jenis' => 'Kerja bakti', 'tipe' => 'Pemasukan']);
    $share = ShareBuku::factory()->create(['buku_kas_id' => $kas->id, 'user_id' => null]);
    $base = ['user_id' => $owner->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $wallet->id, 'tanggal' => '2026-08-10 10:00:00', 'jenis' => 'Pemasukan', 'nominal' => 1000, 'deskripsi' => 'Iuran warga'];
    Transaksi::withoutEvents(function () use ($base, $private, $category): void {
        Transaksi::factory()->count(26)->create($base);
        Transaksi::factory()->create(array_replace($base, ['deskripsi' => 'Kegiatan bersama', 'jenis_transaksi_id' => $category->id]));
        Transaksi::factory()->create(array_replace($base, ['buku_kas_id' => $private->id, 'jenis_transaksi_id' => $category->id]));
        Transaksi::factory()->create(array_replace($base, ['tanggal' => '2025-07-10 10:00:00', 'jenis_transaksi_id' => $category->id]));
    });
    $url = $share->urlPublik().'?bulan=2026-08';
    $result = $this->get($url.'&q=Iuran');
    $result->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 27 && $rows->count() === 25)
        ->assertViewHas('pemasukan', fn ($value): bool => (int) $value === 27000);
    expect($result->getContent())->toContain('Semua tanggal');
    $this->get($share->urlPublik().'?bulan=2024-01&q=Iuran')->assertOk()
        ->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 27);
    $next = $result->viewData('transaksi')->nextPageUrl();
    expect($next)->toContain('q=Iuran', 'bulan=2026-08');
    $this->get($next)->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 27 && $rows->count() === 2);
    $this->get($url.'&q=Kerja')->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 2);
    $this->get($url.'&q=Pemasukan')->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 28);
    $empty = $this->get($url.'&q=tidak-ada');
    $empty->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 0);
    expect($empty->getContent())->toContain('Tidak ada transaksi yang cocok.');
    $this->get($url.'&q=%20%20')->assertOk()->assertViewHas('transaksi', fn ($rows): bool => $rows->total() === 27);
    $this->getJson($url.'&q[]=invalid')->assertUnprocessable();
    $this->getJson($url.'&q='.str_repeat('a', 201))->assertUnprocessable();
    $this->assertGuest();
});
