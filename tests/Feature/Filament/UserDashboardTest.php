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
use App\Models\Kategori;
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
        ->toBe(['ringkasan_mobile', 'jenis', 'tanggal', 'buku_kas.nama_buku', 'sumber_dana', 'kategori', 'nominal']);
    expect($page->html())->toContain('fi-ta-table-stacked-on-mobile', 'data-transaksi-mobile', 'filament-toolbar');
    expect($page->html())->toContain('transaction-record--success');
    Livewire::withQueryParams(['filter_month' => '09', 'filter_year' => '2026'])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($records);
});

test('dashboard user menghitung sisa utang piutang dan tiga pembaruan terakhir', function () {
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

test('dashboard user mempertahankan bagian lain saat satu bagian disembunyikan', function () {
    $user = User::factory()->create();
    $page = Livewire::actingAs($user)->test(Dashboard::class);
    $sections = $page->instance()->sections();
    foreach ($sections as &$section) {
        if (in_array($section['key'], ['dompet', 'piutang'], true)) {
            $section['visible'] = false;
        }
    }
    unset($section);

    $page->call('saveSettings', $sections)->assertHasNoErrors();

    expect(array_column($page->instance()->visibleTabs(), null, 'key'))->toMatchArray([
        'kas' => ['key' => 'kas', 'label' => 'Kas', 'sections' => ['kas']],
        'utang-piutang' => ['key' => 'utang-piutang', 'label' => 'Utang & Piutang', 'sections' => ['utang']],
    ]);
});

test('dashboard user menyimpan toggle dan urutan per akun melalui pengaturan', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $page = Livewire::actingAs($user)->test(Dashboard::class);
    $sections = array_reverse($page->instance()->sections());
    $sections = array_map(fn ($section) => [
        ...$section,
        'visible' => $section['key'] !== 'transaksi',
    ], $sections);
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
    expect($fresh->html())->not->toContain('id="dashboard-toggle-')
        ->and($fresh->html())->toContain('Dashboard disembunyikan');
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

test('dashboard user memakai kartu responsif dengan panel buka tutup', function () {
    $user = User::factory()->create();
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    $grid = $xpath->query('//div[contains(@class,"dashboard-cards")]')->item(0);
    expect($grid)->not->toBeNull();
    $sections = $xpath->query('.//section[@data-section]', $grid);
    expect($sections->length)->toBe(5);
    expect($sections->item(0)->getAttribute('data-section'))->toBe('kas')
        ->and($sections->item(1)->getAttribute('data-section'))->toBe('dompet')
        ->and($sections->item(2)->getAttribute('data-section'))->toBe('utang-piutang')
        ->and($sections->item(3)->getAttribute('data-section'))->toBe('transaksi')
        ->and($sections->item(4)->getAttribute('data-section'))->toBe('langganan');
    expect($xpath->query('.//button[starts-with(@id,"dashboard-toggle-")]', $grid)->length)->toBe(5);
    foreach ($xpath->query('.//section[@data-section]', $grid) as $card) {
        $key = $card->getAttribute('data-section');
        $button = $xpath->query('.//button[@id="dashboard-toggle-'.$key.'"]', $card)->item(0);
        $content = $xpath->query('.//*[@id="dashboard-content-'.$key.'"]', $card)->item(0);
        expect($button->getAttribute('aria-controls'))->toBe($content->getAttribute('id'))
            ->and($button->getAttribute('x-on:click'))->toBe("open['$key'] = ! open['$key']")
            ->and($content->getAttribute('x-show'))->toBe("open['$key']");
    }
    $script = <<<'JS'
        const assert = require('node:assert/strict');
        const initialize = new Function('window', 'return (' + process.argv[1] + ')');
        const mobile = initialize({matchMedia: () => ({matches: false})});
        assert.deepEqual(mobile.open, {'kas': false, 'dompet': false, 'utang-piutang': false, 'transaksi': false, 'langganan': false});
        assert.equal(mobile.anyOpen, false);
        mobile.toggleAll();
        assert.deepEqual(Object.values(mobile.open), [true, true, true, true, true]);
        mobile.toggleAll();
        assert.deepEqual(Object.values(mobile.open), [false, false, false, false, false]);
        const desktop = initialize({matchMedia: () => ({matches: true})});
        assert.deepEqual(desktop.open, {'kas': false, 'dompet': false, 'utang-piutang': false, 'transaksi': false, 'langganan': false});
        assert.equal(desktop.anyOpen, false);
        JS;
    $process = new \Symfony\Component\Process\Process(['node', '-e', $script, $grid->getAttribute('x-data')], base_path());
    $process->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    expect(file_get_contents(resource_path('views/filament/pages/dashboard.blade.php')))->not->toContain('<style');
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
    $cardKas = $xpath->query('//section[@data-section="kas"]')->item(0);
    $cardDompet = $xpath->query('//section[@data-section="dompet"]')->item(0);
    expect($xpath->query('//button[@id="dashboard-toggle-kas"]//*[contains(@class,"dashboard-toggle-icon")][local-name()="svg"]')->length)->toBe(2);
    expect($xpath->query('//button[@id="dashboard-toggle-dompet"]//*[contains(@class,"dashboard-toggle-icon")][local-name()="svg"]')->length)->toBe(2);
    foreach (['kas' => 'Rp 100.000', 'dompet' => 'Rp 80.000'] as $key => $total) {
        $card = $key === 'kas' ? $cardKas : $cardDompet;
        $feature = $xpath->query('.//*[@data-dashboard-item="'.$key.'"]', $card)->item(0);
        expect($feature)->not->toBeNull();
        expect(trim($xpath->query('.//p[contains(@class,"dashboard-amount")]', $feature)->item(0)->textContent))->toBe($total);
    }
});

test('dashboard kas dan sumber dana membuka transaksi dengan filter terpilih', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $kasLain = BukuKas::factory()->create(['user_id' => $user->id]);
    $sumberDana = Dompet::factory()->create(['user_id' => $user->id]);
    $sumberDanaLain = Dompet::factory()->create(['user_id' => $user->id]);
    $transaksiKas = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kas->id,
        'dompet_id' => $sumberDanaLain->id,
        'tanggal' => now(),
    ]));
    $transaksiSumberDana = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasLain->id,
        'dompet_id' => $sumberDana->id,
        'tanggal' => now(),
    ]));
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);

    foreach ([
        ['kas-'.$kas->id, 'filter_buku_kas', $kas->id, 'filter_dompet'],
        ['dompet-'.$sumberDana->id, 'filter_dompet', $sumberDana->id, 'filter_buku_kas'],
    ] as [$key, $filter, $id, $otherFilter]) {
        $link = $xpath->query('//*[@data-dashboard-transaction-filter="'.$key.'"]')->item(0);
        expect($link)->not->toBeNull();
        expect($xpath->query('.//*[local-name()="svg"]', $link)->length)->toBeGreaterThan(0);
        parse_str((string) parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
        expect($query[$filter] ?? null)->toBe((string) $id)
            ->and($query)->not->toHaveKey($otherFilter);
    }

    Livewire::actingAs($user)
        ->withQueryParams(['filter_buku_kas' => (string) $kas->id])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$transaksiKas])
        ->assertCanNotSeeTableRecords([$transaksiSumberDana]);
    Livewire::actingAs($user)
        ->withQueryParams(['filter_dompet' => (string) $sumberDana->id])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$transaksiSumberDana])
        ->assertCanNotSeeTableRecords([$transaksiKas]);
});

test('dashboard user menghubungkan tombol kartu dengan panel konten', function () {
    $user = User::factory()->create();
    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    $cards = $xpath->query('//section[@data-section]');
    expect($cards->length)->toBe(5)
        ->and(array_map(fn ($card) => $card->getAttribute('data-section'), iterator_to_array($cards)))
        ->toBe(['kas', 'dompet', 'utang-piutang', 'transaksi', 'langganan']);
    foreach ($cards as $card) {
        $key = $card->getAttribute('data-section');
        $button = $xpath->query('.//button[@id="dashboard-toggle-'.$key.'"]', $card)->item(0);
        $summary = $xpath->query('./div[@x-cloak and not(@id)]', $card)->item(0);
        $content = $xpath->query('./div[@id="dashboard-content-'.$key.'"]', $card)->item(0);
        expect($button)->not->toBeNull()
            ->and($summary)->not->toBeNull()
            ->and($summary->hasAttribute('x-cloak'))->toBeTrue()
            ->and($content->hasAttribute('x-cloak'))->toBeTrue();
    }
});

test('dashboard user menampilkan satu transaksi terakhir saat kartu transaksi ditutup', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Kas Utama']);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'nama_dompet' => 'BCA Pribadi']);
    $kategori = Kategori::factory()->untukKas($kas)->create(['user_id' => $user->id, 'nama' => 'Makan Siang']);

    // Saat belum ada transaksi
    $htmlKosong = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $domKosong = new DOMDocument;
    @$domKosong->loadHTML('<?xml encoding="utf-8" ?>'.$htmlKosong);
    $xpathKosong = new DOMXPath($domKosong);
    $cardTransaksiKosong = $xpathKosong->query('//section[@data-section="transaksi"]')->item(0);
    $summaryKosong = $xpathKosong->query('./div[@x-cloak and not(@id)]', $cardTransaksiKosong)->item(0);
    expect(trim($summaryKosong->textContent))->toContain('Belum ada transaksi');

    // Buat transaksi
    Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kas->id,
        'sumber_dana_id' => $dompet->id,
        'kategori_id' => $kategori->id,
        'nominal' => 35000,
        'jenis' => 'Pengeluaran',
        'deskripsi' => 'Nasi Padang',
        'tanggal' => '2026-11-14 12:30:00',
    ]));

    $htmlAda = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $domAda = new DOMDocument;
    @$domAda->loadHTML('<?xml encoding="utf-8" ?>'.$htmlAda);
    $xpathAda = new DOMXPath($domAda);
    $cardTransaksiAda = $xpathAda->query('//section[@data-section="transaksi"]')->item(0);
    $summaryAda = $xpathAda->query('./div[@x-cloak and not(@id)]', $cardTransaksiAda)->item(0);
    $text = trim($summaryAda->textContent);

    expect($text)->toContain('Terakhir:')
        ->toContain('Pengeluaran')
        ->toContain('Rp 35.000')
        ->toContain('14 Nov 2026, 12:30')
        ->toContain('Kas: Kas Utama')
        ->toContain('Sumber dana: BCA Pribadi')
        ->toContain('Kategori: Makan Siang')
        ->toContain('(Nasi Padang)')
        ->not->toContain('transaksi terbaru');
});

test('dashboard user tombol tambah membuka form dan menyimpan transaksi', function () {
    $user = User::factory()->create();
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $jenis = Kategori::factory()->untukKas($kas)->create(['user_id' => $user->id, 'tipe' => 'Pemasukan']);
    $page = Livewire::actingAs($user)->test(Dashboard::class)->mountAction('tambahTransaksi')->assertActionMounted('tambahTransaksi');
    $page->setActionData([
        'jenis_form' => 'pemasukan', 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id,
        'kategori_id' => $jenis->id, 'nominal' => 45000, 'tanggal' => now()->format('Y-m-d H:i:s'),
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
    $toolbar = $xpath->query('//section[@data-section="transaksi"]//div[contains(@class,"dashboard-actions")]')->item(0);
    expect($toolbar)->not->toBeNull();
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
    $jenis = Kategori::factory()->untukKas($kas)->create(['user_id' => $user->id, 'tipe' => 'Pemasukan']);
    $this->actingAs($user);
    $data = ['buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id, 'kategori_id' => $jenis->id,
        'nominal' => 50000, 'tanggal' => now()->format('Y-m-d H:i:s'), 'deskripsi' => 'Aksi dashboard'];
    $record = app(TransaksiService::class)->buat($user, $data, 'Pemasukan');
    $page = Livewire::test(Dashboard::class)
        ->assertTableActionVisible('edit', $record)
        ->assertTableActionVisible('delete', $record);
    expect($page->instance()->getTable()->getRecordAction($record))->toBe('edit');
    $page->mountTableAction('edit', $record)
        ->assertActionMounted()
        ->unmountTableAction()
        ->callTableAction('edit', $record, data: [...$data, 'nominal' => 75000])
        ->assertHasNoTableActionErrors();
    expect(str_starts_with($page->html(), '<div'))->toBeTrue();
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
        'langganan' => LanggananResource::class,
    ];
    foreach ($resources as $key => $resource) {
        $links = $xpath->query('//section[@data-section="'.$key.'"]//a[normalize-space(.)="Kelola"]');
        expect($links->length)->toBe(1);
        expect($links->item(0)->getAttribute('href'))->toBe($resource::getUrl('index'));
    }
    $resources = [
        'kas' => ['section' => 'kas', 'resource' => BukuKasResource::class],
        'dompet' => ['section' => 'dompet', 'resource' => DompetResource::class],
        'utang' => ['section' => 'utang-piutang', 'resource' => UtangResource::class],
        'piutang' => ['section' => 'utang-piutang', 'resource' => PiutangResource::class],
    ];
    foreach ($resources as $key => $config) {
        $links = $xpath->query('//section[@data-section="'.$config['section'].'"]//*[@data-dashboard-item="'.$key.'"]//a[normalize-space(.)="Kelola"]');
        expect($links->length)->toBe(1);
        expect($links->item(0)->getAttribute('href'))->toBe($config['resource']::getUrl('index'));
    }
    expect($xpath->query('//section[@data-section="transaksi"]//a[normalize-space(.)="Kelola"]')->length)->toBe(0);
});

test('informasi akses transaksi menjelaskan kas terbatas pada dashboard dan daftar', function () {
    $user = User::factory()->create(['masa_aktif' => null]);
    BukuKas::factory()->count(2)->create(['user_id' => $user->id]);
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $record = Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id, 'buku_kas_id' => $kas->id, 'dompet_id' => $dompet->id, 'tanggal' => now(),
    ]));
    foreach ([Dashboard::class, ListTransaksis::class] as $class) {
        $page = Livewire::actingAs($user)->test($class)
            ->assertTableActionVisible('infoAkses', $record)
            ->assertTableActionHidden('edit', $record)
            ->assertTableActionHidden('delete', $record)
            ->mountTableAction('infoAkses', $record);
        $action = $page->instance()->getMountedAction();
        expect($action->getLabel())->toBe('Kas tidak aktif')
            ->and($action->getColor())->toBe('warning')
            ->and($action->getModalDescription())->toContain('Premium')
            ->and($action->getModalSubmitAction())->toBeNull();
    }
});

test('informasi akses transaksi membedakan alasan dan hilang saat akses aktif', function () {
    $user = User::factory()->create(['masa_aktif' => today()->addWeek()]);
    $this->actingAs($user);
    $kas = BukuKas::factory()->create(['user_id' => $user->id]);
    Dompet::factory()->count(2)->create(['user_id' => $user->id]);
    $dompet = Dompet::factory()->create(['user_id' => $user->id]);
    $record = new Transaksi(['user_id' => $user->id]);
    $record->setRelation('buku_kas', $kas)->setRelation('dompet', $dompet);
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record))->toBeNull();
    $user->masa_aktif = null;
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record)['label'])->toBe('Sumber dana tidak aktif');
    $record->user_id = $user->id + 100;
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record)['label'])->toBe('Hanya dapat dilihat');
    $record->audit_saldo_dompet_detail_id = 123;
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record)['label'])->toBe('Transaksi penyesuaian saldo');
    $record->audit_saldo_dompet_detail_id = null;
    $record->user_id = $user->id;
    $record->setRelation('buku_kas', null);
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record)['label'])->toBe('Akses kas terbatas');
    $user->role = 'admin';
    expect(TransaksiResource::alasanTransaksiTidakDapatDikelola($record))->toBeNull();
});

test('dashboard user menampilkan input cari kas dan sumber dana hanya jika masing-masing memiliki lebih dari lima data', function () {
    $user = User::factory()->create();

    // 1. Kondisi awal: 5 kas dan 5 sumber dana (keduanya <= 5).
    BukuKas::factory()->count(5)->create(['user_id' => $user->id]);
    Dompet::factory()->count(5)->create(['user_id' => $user->id]);

    $html = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//input[@data-testid="cari-kas"]')->length)->toBe(0)
        ->and($xpath->query('//input[@data-testid="cari-sumber-dana"]')->length)->toBe(0);

    // 2. Tambah 1 kas sehingga kas berjumlah 6 (> 5), sumber dana tetap 5 (<= 5).
    BukuKas::factory()->create(['user_id' => $user->id, 'nama_buku' => 'Kas Tabungan Khusus']);

    $htmlKas = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $domKas = new DOMDocument;
    @$domKas->loadHTML('<?xml encoding="utf-8" ?>'.$htmlKas);
    $xpathKas = new DOMXPath($domKas);

    $inputKas = $xpathKas->query('//input[@data-testid="cari-kas"]');
    expect($inputKas->length)->toBe(1)
        ->and($inputKas->item(0)->getAttribute('placeholder'))->toBe('Cari kas...')
        ->and($inputKas->item(0)->getAttribute('aria-label'))->toBe('Cari kas')
        ->and($xpathKas->query('//button[@data-testid="clear-cari-kas"]')->length)->toBe(1)
        ->and($xpathKas->query('//button[@data-testid="clear-cari-kas"]')->item(0)->getAttribute('aria-label'))->toBe('Hapus pencarian kas')
        ->and($xpathKas->query('//input[@data-testid="cari-sumber-dana"]')->length)->toBe(0)
        ->and($xpathKas->query('//button[@data-testid="clear-cari-sumber-dana"]')->length)->toBe(0);

    // 3. Tambah 1 sumber dana sehingga sumber dana berjumlah 6 (> 5), kas tetap 6 (> 5).
    Dompet::factory()->create(['user_id' => $user->id, 'nama_dompet' => 'Rekening Tabungan']);

    $htmlSemua = Livewire::actingAs($user)->test(Dashboard::class)->assertSuccessful()->html();
    $domSemua = new DOMDocument;
    @$domSemua->loadHTML('<?xml encoding="utf-8" ?>'.$htmlSemua);
    $xpathSemua = new DOMXPath($domSemua);

    $inputKasKedua = $xpathSemua->query('//input[@data-testid="cari-kas"]');
    $inputSumberDana = $xpathSemua->query('//input[@data-testid="cari-sumber-dana"]');

    expect($inputKasKedua->length)->toBe(1)
        ->and($xpathSemua->query('//button[@data-testid="clear-cari-kas"]')->length)->toBe(1)
        ->and($inputSumberDana->length)->toBe(1)
        ->and($xpathSemua->query('//button[@data-testid="clear-cari-sumber-dana"]')->length)->toBe(1)
        ->and($xpathSemua->query('//button[@data-testid="clear-cari-sumber-dana"]')->item(0)->getAttribute('aria-label'))->toBe('Hapus pencarian sumber dana')
        ->and($inputSumberDana->item(0)->getAttribute('placeholder'))->toBe('Cari sumber dana...')
        ->and($inputSumberDana->item(0)->getAttribute('aria-label'))->toBe('Cari sumber dana');

    // Pastikan setiap row kas dan sumber dana memiliki data-item-name untuk pencarian Alpine.
    $featureKas = $xpathSemua->query('//div[@data-dashboard-item="kas"]')->item(0);
    $rowsKas = $xpathSemua->query('.//div[contains(@class,"dashboard-row")][@data-item-name]', $featureKas);
    expect($rowsKas->length)->toBe(6);

    $featureDompet = $xpathSemua->query('//div[@data-dashboard-item="dompet"]')->item(0);
    $rowsDompet = $xpathSemua->query('.//div[contains(@class,"dashboard-row")][@data-item-name]', $featureDompet);
    expect($rowsDompet->length)->toBe(6);
});
