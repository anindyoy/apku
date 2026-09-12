<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\BukuKasResource;
use App\Filament\Resources\DompetResource;
use App\Filament\Resources\LanggananResource;
use App\Filament\Resources\PiutangResource;
use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\UtangResource;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Services\TransaksiService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('dashboard user menampilkan saldo milik sendiri dan lima transaksi terbaru dengan kolom bersama', function () {
    $this->travelTo(Carbon::parse('2026-09-11 12:00:00'));
    $user = User::factory()->create();
    $other = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 120000]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'saldo' => 90000]);
    BukuKas::factory()->create(['user_id' => $other->id]);
    Dompet::factory()->create(['user_id' => $other->id]);
    $records = collect();
    for ($i = 0; $i < 7; $i++) {
        $records->push(Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
            'user_id' => $user->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
            'tanggal' => now()->subDays($i), 'jenis' => 'Pemasukan',
        ])));
    }
    $foreign = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $other->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
        'tanggal' => now(),
    ]));
    $page = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->assertNoRedirect();
    expect($page->instance()->sectionData('kas')->modelKeys())->toBe([$kas->id])
        ->and((float) $page->instance()->sectionData('kas')->first()->saldo)->toBe(120000.0)
        ->and($page->instance()->sectionData('dompet')->modelKeys())->toBe([$dompet->id])
        ->and((float) $page->instance()->sectionData('dompet')->first()->saldo)->toBe(90000.0);
    $page->assertCanSeeTableRecords($records->take(5), inOrder: true)
        ->assertCanNotSeeTableRecords($records->skip(5)->push($foreign));
    expect($page->instance()->getTableRecords())->toHaveCount(5);
    expect(array_keys($page->instance()->getTable()->getColumns()))
        ->toBe(['jenis', 'tanggal', 'buku_kas.nama_buku', 'kategori', 'nominal']);
    Livewire::test(ListTransaksis::class)->assertSuccessful()->assertCanSeeTableRecords($records);
});

test('dashboard user menghitung sisa utang piutang dan tiga aktivitas terakhir', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $expected = [];
    foreach (['utang', 'piutang'] as $type) {
        for ($i = 0; $i < 4; $i++) {
            $debt = UtangPiutang::factory()->create(['user_id' => $user->id, 'tipe' => $type]);
            $debt->utang_piutang_detail()->create(['tipe' => 'tambah', 'nominal' => 100000, 'created_at' => now()->subDays($i + 1)]);
            $debt->utang_piutang_detail()->create(['tipe' => 'kurang', 'nominal' => 25000, 'created_at' => now()->subDays($i)]);
            $expected[$type][] = $debt->id;
        }
        $foreign = UtangPiutang::factory()->create(['user_id' => $other->id, 'tipe' => $type]);
        $foreign->utang_piutang_detail()->create(['tipe' => 'tambah', 'nominal' => 999999]);
    }
    $page = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful();
    foreach (['utang', 'piutang'] as $type) {
        $data = $page->instance()->sectionData($type);
        expect((float) $data['total'])->toBe(300000.0)
            ->and($data['latest']->modelKeys())->toBe(array_slice($expected[$type], 0, 3));
    }
});

test('dashboard user menyimpan toggle dan urutan per akun melalui pengaturan', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $page = Livewire::actingAs($user)->test(Dashboard::class);
    $sections = array_reverse($page->instance()->sections());
    $sections[0]['visible'] = false;
    $page->callAction('aturDashboard', data: ['sections' => $sections])->assertHasNoActionErrors();
    expect($user->fresh()->dashboard_settings)->toBe($sections)
        ->and($other->fresh()->dashboard_settings)->toBeNull();
    $fresh = Livewire::test(Dashboard::class)->assertSuccessful();
    expect($fresh->instance()->sections())->toBe($sections);
    $html = $fresh->html();
    expect($html)->not->toContain('wire:key="dashboard-transaksi"')
        ->and(strpos($html, 'wire:key="dashboard-langganan"'))->toBeLessThan(strpos($html, 'wire:key="dashboard-kas"'));
    $hidden = array_map(fn ($section) => [...$section, 'visible' => false], $sections);
    $fresh->call('saveSettings', $hidden)->assertHasNoErrors();
    expect($fresh->html())->not->toContain('wire:key="dashboard-');
});

test('dashboard user menolak pengaturan tidak valid dan aksi admin', function () {
    $user = User::factory()->create();
    $page = Livewire::actingAs($user)->test(Dashboard::class);
    $sections = $page->instance()->sections();
    $sections[0]['key'] = 'asing';
    $page->call('saveSettings', $sections)->assertHasErrors(['sections.0.key']);
    $sections = $page->instance()->sections();
    $sections[0]['key'] = $sections[1]['key'];
    $page->call('saveSettings', $sections)->assertHasErrors(['sections.0.key']);
    expect($user->fresh()->dashboard_settings)->toBeNull();
    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(Dashboard::class)->assertSuccessful()
        ->call('saveSettings', [])->assertForbidden();
});

test('dashboard user menampilkan masa aktif dan keadaan kosong', function (?string $expiry, bool $active) {
    $user = User::factory()->create(['masa_aktif' => $expiry]);
    $page = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful();
    expect($page->instance()->sectionData('langganan')['active'])->toBe($active)
        ->and($page->instance()->sectionData('utang')['latest'])->toHaveCount(0)
        ->and($page->instance()->sectionData('utang')['total'])->toEqual(0);
    $page->assertCountTableRecords(0);
})->with([
    'tanpa langganan' => [null, false],
    'kedaluwarsa' => [fn () => today()->subDay()->toDateString(), false],
    'hari terakhir' => [fn () => today()->toDateString(), true],
    'masih aktif' => [fn () => today()->addDays(10)->toDateString(), true],
]);

test('dashboard user memakai grid responsif tiga kolom dan tabel selebar halaman', function () {
    $user = User::factory()->create();
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    $grid = $xpath->query('//div[@class="dashboard-user-grid"]')->item(0);
    expect($grid)->not->toBeNull();
    expect($xpath->query('./section', $grid)->length)->toBe(6);
    $table = $xpath->query('./section[@*[name()="wire:key"]="dashboard-transaksi"]', $grid)->item(0);
    expect($table->getAttribute('class'))->toContain('dashboard-full-width');
    $styles = $xpath->query('//style')->item(0)->textContent;
    expect($styles)->toContain('grid-template-columns: minmax(0, 1fr)')
        ->toContain('@media (min-width: 768px)')
        ->toContain('repeat(2, minmax(0, 1fr))')
        ->toContain('@media (min-width: 1280px)')
        ->toContain('repeat(3, minmax(0, 1fr))');
});

test('dashboard user merangkum saldo kartu dengan ikon dan aksen', function () {
    $user = User::factory()->create();
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => 125000]);
    BukuKas::factory()->create(['user_id' => $user->id, 'saldo' => -25000]);
    Dompet::factory()->create(['user_id' => $user->id, 'saldo' => 80000]);
    $other = User::factory()->create();
    BukuKas::factory()->create(['user_id' => $other->id, 'saldo' => 999999]);
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    foreach (['kas' => 'Rp 100.000', 'dompet' => 'Rp 80.000'] as $key => $total) {
        $card = $xpath->query('//section[@data-section="'.$key.'"]')->item(0);
        expect($card->getAttribute('class'))->toContain('dashboard-card');
        expect($xpath->query('.//header/*[local-name()="svg"]', $card)->length)->toBe(1);
        expect(trim($xpath->query('.//p[@class="dashboard-amount"]', $card)->item(0)->textContent))->toBe($total);
    }
});

test('dashboard user menyediakan kontrol lipat independen untuk setiap kartu', function () {
    $user = User::factory()->create();
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    $cards = $xpath->query('//section[@data-section]');
    expect($cards->length)->toBe(6);
    foreach ($cards as $card) {
        expect($card->getAttribute('class'))->toContain('fi-collapsible');
        $button = $xpath->query('.//button[contains(@class,"fi-section-collapse-btn")]', $card)->item(0);
        expect($button)->not->toBeNull();
        expect($button->getAttribute('aria-expanded'))->toBe('true')
            ->and($button->getAttribute('x-on:click.stop'))->toBe('isCollapsed = ! isCollapsed')
            ->and($button->getAttribute('x-bind:aria-expanded'))->toBe('(! isCollapsed).toString()');
        expect($xpath->query('.//div[@class="fi-section-content-ctn"]', $card)->length)->toBe(1);
    }
});

test('dashboard user tombol tambah membuka form dan menyimpan transaksi', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $jenis = JenisTransaksi::factory()->create(['user_id' => $user->id, 'tipe' => 'Pemasukan']);
    $page = Livewire::actingAs($user)->test(Dashboard::class)->mountAction('tambahTransaksi')->assertActionMounted('tambahTransaksi');
    $page->setActionData([
        'jenis_form' => 'pemasukan', 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
        'jenis_transaksi_id' => $jenis->id, 'nominal' => 45000, 'tanggal' => now()->format('Y-m-d H:i:s'),
        'deskripsi' => 'Transaksi dari dashboard',
    ])->callMountedAction()->assertHasNoActionErrors();
    $record = Transaksi::where('user_id', $user->id)->sole();
    expect((float) $kas->fresh()->saldo)->toBe(45000.0)
        ->and((float) $dompet->fresh()->saldo)->toBe(45000.0);
    $page->assertCanSeeTableRecords([$record]);
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$page->html());
    $xpath = new DOMXPath($dom);
    $link = $xpath->query('//section[@data-section="transaksi"]//a[contains(.,"Lihat lengkap")]')->item(0);
    expect($link->getAttribute('href'))->toBe(TransaksiResource::getUrl());
    $toolbar = $xpath->query('//section[@data-section="transaksi"]//div[@class="dashboard-actions"]')->item(0);
    expect($toolbar)->not->toBeNull();
    expect($xpath->query('//style')->item(0)->textContent)
        ->toMatch('/\.dashboard-actions\s*\{[^}]*justify-content:\s*flex-end;/');
    expect($xpath->query('.//button', $toolbar)->length)->toBe(1);
    expect($xpath->query('.//a', $toolbar)->length)->toBe(1);
    expect($xpath->query('following-sibling::*//table', $toolbar)->length)->toBe(1);
});

test('dashboard user tombol tambah mengikuti hak akses', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(Dashboard::class)->assertActionHidden('tambahTransaksi');
    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(Dashboard::class)->assertActionHidden('tambahTransaksi');
});

test('dashboard user aksi baris mengubah dan menghapus transaksi beserta saldo', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $jenis = JenisTransaksi::factory()->create(['user_id' => $user->id, 'tipe' => 'Pemasukan']);
    $this->actingAs($user);
    $data = ['buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id, 'jenis_transaksi_id' => $jenis->id,
        'nominal' => 50000, 'tanggal' => now()->format('Y-m-d H:i:s'), 'deskripsi' => 'Aksi dashboard'];
    $record = app(TransaksiService::class)->buat($user, $data, 'Pemasukan');
    $page = Livewire::test(Dashboard::class)
        ->assertTableActionVisible('edit', $record)
        ->assertTableActionVisible('delete', $record)
        ->callTableAction('edit', $record, data: [...$data, 'nominal' => 75000])
        ->assertHasNoTableActionErrors();
    expect((float) $record->fresh()->nominal)->toBe(75000.0)
        ->and((float) $kas->fresh()->saldo)->toBe(75000.0)
        ->and((float) $dompet->fresh()->saldo)->toBe(75000.0);
    $page->callTableAction('delete', $record)->assertHasNoTableActionErrors();
    expect($record->fresh())->toBeNull()
        ->and((float) $kas->fresh()->saldo)->toBe(0.0)
        ->and((float) $dompet->fresh()->saldo)->toBe(0.0);
});

test('dashboard user aksi baris disembunyikan pada kas terbatas', function () {
    $user = User::factory()->create(['masa_aktif' => null]);
    BukuKas::factory()->count(2)->create(['user_id' => $user->id]);
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $record = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
    ]));
    Livewire::actingAs($user)->test(Dashboard::class)
        ->assertCanSeeTableRecords([$record])
        ->assertTableActionHidden('edit', $record)
        ->assertTableActionHidden('delete', $record);
});

test('dashboard user tombol kelola menuju resource setiap kartu selain transaksi', function () {
    $user = User::factory()->create();
    $page = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$page->html());
    $xpath = new DOMXPath($dom);
    $resources = [
        'kas' => BukuKasResource::class,
        'dompet' => DompetResource::class,
        'utang' => UtangResource::class,
        'piutang' => PiutangResource::class,
        'langganan' => LanggananResource::class,
    ];
    foreach ($resources as $key => $resource) {
        $links = $xpath->query('//section[@data-section="'.$key.'"]//a[normalize-space(.)="Kelola"]');
        expect($links->length)->toBe(1);
        expect($links->item(0)->getAttribute('href'))->toBe($resource::getUrl('index'));
    }
    expect($xpath->query('//section[@data-section="transaksi"]//a[normalize-space(.)="Kelola"]')->length)->toBe(0);
});
