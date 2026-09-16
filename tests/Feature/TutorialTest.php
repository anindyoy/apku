<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Livewire\Livewire;
use Symfony\Component\Process\Process;

it('tutorial sidebar mengikuti topik hash scroll dan hasil pencarian', function () {
    $script = <<<'JS'
    import assert from 'node:assert/strict';
    import { initTutorialSidebar } from './public/js/tutorial-sidebar.js';
    const events = {};
    let tops = [0, 500, 1000];
    const chapters = [{ open: true }, { open: true }, { open: false }];
    const links = ['akun', 'dompet', 'laporan'].map((id, i) => ({
        hash: `#${id}`, attrs: {},
        setAttribute(name, value) { this.attrs[name] = value; },
        removeAttribute(name) { delete this.attrs[name]; },
        addEventListener(name, callback) { this[name] = callback; },
        getBoundingClientRect: () => ({ top: i * 100, bottom: i * 100 + 40 }),
    }));
    const articles = Object.fromEntries(links.map((link, i) => [link.hash.slice(1), {
        closest: () => chapters[i],
        getBoundingClientRect: () => ({ top: tops[i] }),
    }]));
    const position = { textContent: '' };
    const sidebar = { querySelectorAll: () => links, scrollHeight: 400, clientHeight: 150, scrollTop: 0, getBoundingClientRect: () => ({ top: 0, bottom: 150 }) };
    const doc = { querySelector: selector => selector === '[data-tutorial-sidebar]' ? sidebar : position, getElementById: id => articles[id] };
    const win = { location: { hash: '#laporan' }, addEventListener: (name, fn) => { events[name] = fn; }, requestAnimationFrame: fn => fn() };
    initTutorialSidebar(doc, win);
    assert.equal(chapters[2].open, true);
    assert.equal(position.textContent, 'Topik 3 dari 3 yang ditampilkan');
    assert.equal(links[2].attrs['aria-current'], 'location');
    assert.ok(sidebar.scrollTop > 0);
    win.location.hash = '#dompet';
    events.hashchange();
    assert.equal(links[1].attrs['aria-current'], 'location');
    assert.equal(links[2].attrs['aria-current'], undefined);
    chapters[1].open = false;
    links[1].click();
    assert.equal(chapters[1].open, true);
    tops = [-500, -20, 450];
    events.scroll();
    assert.equal(position.textContent, 'Topik 2 dari 3 yang ditampilkan');
    tops = [-1000, -500, 50];
    events.scroll();
    assert.equal(links[2].attrs['aria-current'], 'location');
    tops = [400, 900, 1400];
    win.location.hash = '#atas';
    events.hashchange();
    assert.equal(position.textContent, 'Topik 1 dari 3 yang ditampilkan');
    sidebar.querySelectorAll = () => [links[1]];
    initTutorialSidebar(doc, win);
    assert.equal(position.textContent, 'Topik 1 dari 1 yang ditampilkan');
    sidebar.querySelectorAll = () => [];
    position.textContent = '0 topik ditampilkan';
    initTutorialSidebar(doc, win);
    assert.equal(position.textContent, '0 topik ditampilkan');
    JS;
    $process = new Process(['node', '--input-type=module'], base_path());
    $process->setInput($script)->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    foreach (['' => 18, 'XLSX' => 2, 'tidakadatopik123' => 0] as $query => $count) {
        $response = $this->get(route('tutorial', ['q' => $query]))->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $links = $xpath->query('//a[@data-topic-link]');
        expect($links)->toHaveCount($count);
        foreach ($links as $link) {
            expect(trim($link->textContent))->not->toMatch('/^\d+\. /');
        }
        expect($xpath->query('//p[@data-topic-position]')->item(0)->textContent)->toBe($count.' topik ditampilkan');
        expect($xpath->query('//script[@src="'.asset('js/tutorial-sidebar.js').'"]'))->toHaveCount(1);
    }
});

it('tutorial kontekstual mengikuti URL aktif dan perubahan navigasi panel', function () {
    $script = <<<'JS'
    import assert from 'node:assert/strict';
    import { readFileSync } from 'node:fs';
    const base = 'https://apku.test/tutorial';
    const panel = 'https://apku.test/admin';
    const link = { dataset: { tutorialUrl: base, panelUrl: panel }, href: base };
    const events = {};
    globalThis.window = { location: { href: `${panel}/dompet` } };
    globalThis.document = {
        querySelectorAll: () => [link],
        addEventListener: (name, callback) => { events[name] = callback; },
    };
    const { tutorialUrl } = await import('./public/js/tutorial-context.js');
    assert.equal(link.href, `${base}#dompet`);
    const cases = {
        '': 'dashboard', 'akun-saya': 'profil-notifikasi', profile: 'profil-notifikasi',
        onboarding: 'pengaturan-awal', transaksi: 'transaksi', 'pencarian-transaksi': 'pencarian',
        'riwayat-import-transaksi': 'import', 'buku-kas': 'kas', dompet: 'dompet',
        'audit-saldo-dompet': 'audit-saldo', kategori: 'aktivitas', 'kolaborator-kas': 'kolaborasi',
        'tabungan-emas': 'emas', laporan: 'laporan', utangs: 'utang-piutang',
        piutangs: 'utang-piutang', langganans: 'langganan',
    };
    const ids = JSON.parse(readFileSync('resources/content/tutorial.json', 'utf8')).map(topic => topic.id);
    for (const [path, topic] of Object.entries(cases)) {
        assert.ok(ids.includes(topic));
        assert.equal(tutorialUrl(base, `${panel}/${path}?bulan=9#modal`, panel), `${base}#${topic}`);
    }
    for (const path of ['langganans/create', 'utangs/123/detail', 'piutangs/456/detail']) {
        assert.equal(tutorialUrl(base, `${panel}/${path}`, panel), `${base}#${cases[path.split('/')[0]]}`);
    }
    for (const path of ['setting', 'users', 'paket-langganans', 'voucher-codes', 'vouchers', 'metode-pembayarans', 'dompet-lain', 'toString']) {
        assert.equal(tutorialUrl(base, `${panel}/${path}`, panel), base);
    }
    assert.equal(tutorialUrl(base, 'https://other.test/admin/dompet', panel), base);
    assert.equal(tutorialUrl(base, 'https://apku.test/administrator/dompet', panel), base);
    assert.equal(tutorialUrl(base, 'https://apku.test/sub/admin/dompet', 'https://apku.test/sub/admin/'), `${base}#dompet`);
    for (const event of ['livewire:navigated', 'DOMContentLoaded', 'pointerdown', 'focusin', 'click', 'contextmenu']) {
        link.href = base;
        window.location.href = `${panel}/laporan?bulan=9`;
        events[event]();
        assert.equal(link.href, `${base}#laporan`);
    }
    console.log('Pemetaan topik dan navigasi tutorial lulus');
    JS;

    $process = new Process(['node', '--input-type=module'], base_path());
    $process->setInput($script)->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
});

it('tutorial tersedia sebagai tombol tanda tanya di sisi kanan topbar panel', function (bool $admin) {
    $user = $admin ? User::query()->admin()->firstOrFail() : User::query()->notAdmin()->firstOrFail();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    $html = Livewire::test(Topbar::class)->assertSuccessful()->html();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " fi-topbar-end ")]//a[@data-testid="topbar-tutorial"]');

    expect($buttons)->toHaveCount(1);
    expect($buttons->item(0)->getAttribute('href'))->toBe(route('tutorial'));
    expect($buttons->item(0)->getAttribute('target'))->toBe('_blank');
    expect($buttons->item(0)->getAttribute('data-tutorial-url'))->toBe(route('tutorial'));
    expect($buttons->item(0)->getAttribute('data-panel-url'))->toBe(Filament::getPanel('admin')->getUrl());
    expect($xpath->query('//script[@type="module" and @src="'.asset('js/tutorial-context.js').'"]'))->toHaveCount(1);
    expect(explode(' ', $buttons->item(0)->getAttribute('rel')))->toContain('noopener', 'noreferrer');
    expect($buttons->item(0)->getAttribute('aria-label'))->toBe('Tutorial Penggunaan');
    expect($xpath->query('.//*[local-name()="svg"]', $buttons->item(0)))->toHaveCount(1);
    $expectedIcon = new DOMDocument;
    $expectedIcon->loadXML(svg('heroicon-o-question-mark-circle')->toHtml());
    expect($xpath->query('.//*[local-name()="path"]', $buttons->item(0))->item(0)->getAttribute('d'))
        ->toBe($expectedIcon->getElementsByTagName('path')->item(0)->getAttribute('d'));
})->with([false, true]);

it('tutorial tidak menampilkan tombol topbar bagi tamu', function () {
    expect(view('filament.components.topbar-tutorial')->render())->not->toContain('data-testid="topbar-tutorial"');
});

it('tutorial publik menampilkan seluruh topik dan tautan daftar isi tanpa login', function () {
    $response = $this->get(route('tutorial'))->assertOk()->assertViewIs('tutorial');
    $topics = $response->viewData('topics');

    expect($topics)->toHaveCount(18);
    expect(array_column($topics, 'id'))->toBe([
        'akun', 'pengaturan-awal', 'dashboard', 'transaksi', 'transfer', 'pencarian',
        'import', 'kas', 'kolaborasi', 'kas-publik', 'dompet', 'audit-saldo',
        'aktivitas', 'emas', 'laporan', 'utang-piutang', 'langganan', 'profil-notifikasi',
    ]);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    foreach ($topics as $topic) {
        expect($topic['page'])->not->toBeEmpty();
        expect($topic['category'])->not->toBeEmpty();
        expect($topic['intro'])->not->toBeEmpty();
        expect($topic['example'])->not->toBeEmpty();
        expect($topic['note'])->not->toBeEmpty();
        expect(count($topic['steps']))->toBeGreaterThanOrEqual(3);
        expect($xpath->query('//article[@id="'.$topic['id'].'"]'))->toHaveCount(1);
        expect($xpath->query('//a[@href="#'.$topic['id'].'"]'))->toHaveCount(1);
        expect($xpath->query('//article[@id="'.$topic['id'].'"]/ol/li'))->toHaveCount(count($topic['steps']));
        expect($xpath->query('//main/details[@data-tutorial-chapter]/summary[text()="'.$topic['page'].'"]/../article[@id="'.$topic['id'].'"]'))->toHaveCount(1);
    }
    expect($xpath->query('//aside/nav/div[@class="page-group"]'))->toHaveCount(count($response->viewData('groups')));
    expect($xpath->query('//main/details[@data-tutorial-chapter]'))->toHaveCount(count($response->viewData('groups')));
    expect($xpath->query('//main/details[@data-tutorial-chapter and @open]'))->toHaveCount(1);
    $this->assertGuest();
});

it('tutorial mengelompokkan topik menurut halaman fitur dan menyembunyikan kelompok tanpa hasil', function () {
    $response = $this->get(route('tutorial', ['q' => 'Viewer']))->assertOk();
    $groups = $response->viewData('groups');

    expect($groups->keys()->all())->toBe(['Kas', 'Langganan Premium']);
    expect(array_column($response->viewData('topics'), 'id'))->toBe(['kolaborasi', 'kas-publik', 'langganan']);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//aside/nav/div[@class="page-group"]'))->toHaveCount(2);
    expect($xpath->query('//main/details[@data-tutorial-chapter]'))->toHaveCount(2);
    expect($xpath->query('//aside/nav//a[@data-topic-link]'))->toHaveCount(3);
});

it('tutorial mencari isi panduan tanpa membedakan kapital dan mengabaikan spasi tepi', function () {
    $response = $this->get(route('tutorial', ['q' => '  XLSX  ']))->assertOk();

    expect(array_column($response->viewData('topics'), 'id'))->toBe(['import', 'laporan']);
    expect($response->viewData('query'))->toBe('XLSX');
    expect($response->viewData('total'))->toBe(18);
});

it('tutorial menampilkan kondisi kosong dan mengamankan teks pencarian', function () {
    $query = '<script>alert(1)</script>';
    $response = $this->get(route('tutorial', ['q' => $query]))->assertOk();

    expect($response->viewData('topics'))->toBe([]);
    expect($response->getContent())->toContain('Panduan belum ditemukan', e($query));
    expect($response->getContent())->not->toContain($query);
});

it('tutorial menolak pencarian yang bukan string atau terlalu panjang', function () {
    foreach ([['q' => ['kas']], ['q' => str_repeat('a', 101)]] as $parameters) {
        $this->getJson(route('tutorial', $parameters))->assertUnprocessable()->assertJsonValidationErrors('q');
    }
});

it('tutorial dapat ditemukan dari login dan navigasi panel', function () {
    $response = $this->get('/admin/login')->assertOk();
    expect($response->getContent())->toContain('href="'.route('tutorial').'"');

    $item = collect(Filament::getPanel('admin')->getNavigationItems())
        ->first(fn ($item) => $item->getLabel() === 'Tutorial Penggunaan');
    expect($item)->not->toBeNull();
    expect($item->getUrl())->toBe(route('tutorial'));

    $this->actingAs(User::query()->notAdmin()->firstOrFail());
    expect($item->isVisible())->toBeTrue();
    $this->get(route('tutorial'))->assertOk();

    $this->actingAs(User::query()->admin()->firstOrFail());
    expect($item->isVisible())->toBeFalse();
});
