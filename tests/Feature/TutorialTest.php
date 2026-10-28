<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Livewire\Livewire;
use Symfony\Component\Process\Process;

test('tutorial laporan menjelaskan posisi grup setelah utang piutang', function () {
    $response = $this->get(route('tutorial', ['q' => 'Membaca dan mengekspor laporan']))->assertOk();
    $topic = collect($response->viewData('topics'))->firstWhere('id', 'laporan');
    expect($topic['intro'])->toContain('Grup menu Laporan berada tepat di bawah grup Utang Piutang');
    expect(implode(' ', $topic['steps']))->toContain('data dimuat otomatis', 'Memuat laporan...');
});

test('tutorial emas menjelaskan kartu mobile dan tabel desktop', function () {
    $response = $this->get(route('tutorial', ['q' => 'Tabungan emas']))->assertOk();
    $topic = collect($response->viewData('topics'))->firstWhere('id', 'emas');
    expect($topic['intro'])->toContain('kartu', 'label emas, berat, harga beli, keterangan', 'Dibeli pada', 'hak akses', 'desktop', 'tabel', 'total berat per kas');
});

test('tutorial kolaborator menjelaskan kartu dan aksi link publik', function () {
    $topics = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');
    expect($topics['kolaborasi']['intro'])->toContain('card', 'satu kolom', 'hak akses', 'tanggal Mulai dan Berlaku hingga');
    expect(implode(' ', $topics['kolaborasi']['steps']))->toContain('aksi pada card kolaborator');
    expect(implode(' ', $topics['kas-publik']['steps']))->toContain('Salin link pada card kolaborator publik');
});

test('tutorial transaksi menjelaskan header desktop dan ringkasan mobile', function () {
    $topik = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');
    expect($topik['transaksi']['intro'])->toContain('Tipe (ikon jenis transaksi), Tanggal, Kas, Kategori, lalu Nominal', 'Pencarian Transaksi', 'Dashboard', 'layar kecil');
    expect(implode(' ', $topik['transaksi']['steps']))->toContain('form kategori yang sama seperti di Setting', 'Pengguna Premium aktif dapat membuka form kas atau sumber dana');
    expect(implode(' ', $topik['kategori']['steps']))->toContain('akun Reguler maupun Premium juga dapat membuat kategori langsung');
    expect($topik['transaksi']['note'])->toContain('Dicatat oleh', 'pernah dikolaborasikan dengan akun lain', 'kedaluwarsa atau dicabut', 'Tautan kas publik');
});

test('tutorial pencocokan saldo menjelaskan penghitung pecahan uang', function () {
    $topics = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');

    expect(implode(' ', $topics['audit-saldo']['steps']))->toContain('Cocokkan saldo pada kartu sumber dana', 'Cocokkan saldo dari kartu hanya mencakup sumber dana tersebut', 'hanya ditampilkan pada sumber dana berjenis Tunai', 'dua kolom pada layar lebar', 'kas tempat sumber dana itu memiliki transaksi', 'Catatan pencocokan boleh dikosongkan', 'Catatan sumber dana tidak tersedia pada form pencocokan', 'total kas dan tombol Ulangi tampil dua kolom', 'Ulangi')
        ->and($topics['audit-saldo']['note'])->toContain('Rp100.000', 'Rp500');
});

test('tutorial dashboard menjelaskan tata letak tab mobile', function () {
    $topics = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');
    $steps = implode(' ', $topics['dashboard']['steps']);

    expect($steps)->toContain('ponsel tab tersusun dua kolom')
        ->not->toContain('geser baris tab ke samping');
});

test('tutorial langganan menjelaskan tombol salin nomor tujuan', function () {
    $topik = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');

    expect(implode(' ', $topik['langganan']['steps']))->toContain('tombol Salin');
});

test('tutorial trial hanya menampilkan ajakan kepada pengguna yang memenuhi syarat', function () {
    $topik = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');

    expect(implode(' ', $topik['trial-premium']['steps']))
        ->toContain('Ajakan trial hanya ditampilkan kepada pengguna yang memenuhi syarat')
        ->not->toContain('arahan pada kartu');
});

test('tutorial kuota kas dan sumber dana menjelaskan batas serta masa aktif premium', function () {
    $topik = collect(json_decode(file_get_contents(resource_path('content/tutorial.json')), true, flags: JSON_THROW_ON_ERROR))->keyBy('id');

    foreach (['kas', 'dompet'] as $id) {
        expect($topik[$id]['note'])->toContain('maksimal 2', 'sisa slot', 'aksi tambah disembunyikan', 'Setelah Premium berakhir', 'satu kalimat', 'Latar kuning', 'latar merah');
        expect($topik[$id]['note'])->toContain('tidak menampilkan pesan kuota');
    }

    expect($topik['dompet']['note'])->toContain('minimal dua dan maksimal tiga kolom', 'satu atau dua sumber dana', 'tiga atau lebih');
    expect(implode(' ', $topik['dompet']['steps']))->toContain('nama sumber dana yang sudah dihapus boleh digunakan kembali');
    expect($topik['kas']['note'])->toContain('Kas bersama tidak dihitung');
    $this->get('/tutorial')->assertSuccessful();
});

it('tutorial sidebar mengikuti topik hash scroll dan hasil pencarian', function () {
    $script = <<<'JS'
    import assert from 'node:assert/strict';
    import { initTutorialSidebar } from './public/js/tutorial-sidebar.js';
    const events = {};
    const sections = [{ hidden: false }, { hidden: false }, { hidden: false }];
    const links = ['akun', 'dompet', 'laporan'].map((id, i) => {
        const link = {
            hash: `#${id}`, textContent: id.toUpperCase(), attrs: {},
            setAttribute(name, value) { this.attrs[name] = value; },
            removeAttribute(name) { delete this.attrs[name]; },
            addEventListener(name, callback) { this[name] = callback; },
            click() { this.click?.(); },
            getBoundingClientRect: () => ({ top: i * 100, bottom: i * 100 + 40 }),
        };
        return link;
    });
    const subHeadings = {
        'laporan-langkah': { id: 'laporan-langkah' },
        'laporan-contoh': { id: 'laporan-contoh' },
    };
    const articles = Object.fromEntries(links.map((link, i) => [link.hash.slice(1), {
        id: link.hash.slice(1),
        hidden: false,
        closest: selector => selector === 'section' ? sections[i] : (selector === 'article[id]' ? articles[link.hash.slice(1)] : null),
        scrollIntoView() { this.scrolled = true; },
    }]));
    const position = { textContent: '' };
    const mobileNav = { open: true };
    const sidebar = {
        querySelector: selector => selector === '[data-mobile-nav]' ? mobileNav : null,
        querySelectorAll: () => links,
        scrollHeight: 400, clientHeight: 150, scrollTop: 0,
        getBoundingClientRect: () => ({ top: 0, bottom: 150 }),
    };
    const tocLinks = {
        laporan: [
            { hash: '#laporan-langkah', attrs: {}, setAttribute(n, v) { this.attrs[n] = v; }, removeAttribute(n) { delete this.attrs[n]; } },
            { hash: '#laporan-contoh', attrs: {}, setAttribute(n, v) { this.attrs[n] = v; }, removeAttribute(n) { delete this.attrs[n]; } },
        ],
        dompet: [
            { hash: '#dompet-langkah', attrs: {}, setAttribute(n, v) { this.attrs[n] = v; }, removeAttribute(n) { delete this.attrs[n]; } },
        ],
    };
    const tocLists = [
        { getAttribute: () => 'dompet', hidden: true, querySelectorAll: () => tocLinks.dompet, addEventListener: () => {} },
        { getAttribute: () => 'laporan', hidden: true, querySelectorAll: () => tocLinks.laporan, addEventListener: () => {} },
    ];
    const tocRoot = { hidden: true };
    const doc = {
        title: 'APKu',
        querySelector: selector => {
            if (selector === '[data-tutorial-sidebar]') return sidebar;
            if (selector === '[data-topic-position]') return position;
            if (selector === '[data-toc]') return tocRoot;
            return null;
        },
        querySelectorAll: selector => selector === '[data-toc-for]' ? tocLists : [],
        getElementById: id => articles[id] ?? subHeadings[id] ?? null,
    };
    let spyCallback = null;
    let observed = [];
    class FakeIntersectionObserver {
        constructor(callback) { spyCallback = callback; }
        observe(target) { observed.push(target); }
        disconnect() { observed = []; }
    }
    const win = {
        location: { hash: '#laporan' },
        addEventListener: (name, fn) => { events[name] = fn; },
        matchMedia: () => ({ matches: false }),
        IntersectionObserver: FakeIntersectionObserver,
    };
    initTutorialSidebar(doc, win);
    assert.equal(articles.laporan.hidden, false);
    assert.equal(articles.dompet.hidden, true);
    assert.equal(sections[2].hidden, false);
    assert.equal(sections[1].hidden, true);
    assert.equal(position.textContent, 'Topik 3 dari 3 yang ditampilkan');
    assert.equal(links[2].attrs['aria-current'], 'location');
    assert.ok(sidebar.scrollTop > 0);
    assert.equal(doc.title, 'LAPORAN · APKu');
    assert.equal(tocRoot.hidden, false);
    assert.equal(tocLists[1].hidden, false);
    assert.equal(tocLists[0].hidden, true);
    assert.equal(tocLinks.laporan[0].attrs['aria-current'], 'location');

    // Scroll spy mengamati sub-bagian di dalam artikel laporan
    assert.equal(observed.length, 2);
    spyCallback([{ isIntersecting: true, target: subHeadings['laporan-contoh'] }]);
    assert.equal(tocLinks.laporan[1].attrs['aria-current'], 'location');
    assert.equal(tocLinks.laporan[0].attrs['aria-current'], undefined);

    // Di mobile, klik tautan menutup navigasi lipat
    links[1].click();
    assert.equal(mobileNav.open, false);

    // Navigasi hashchange ke topik dompet
    win.location.hash = '#dompet';
    events.hashchange();
    assert.equal(articles.dompet.hidden, false);
    assert.equal(articles.laporan.hidden, true);
    assert.equal(position.textContent, 'Topik 2 dari 3 yang ditampilkan');
    assert.equal(links[1].attrs['aria-current'], 'location');
    assert.equal(links[2].attrs['aria-current'], undefined);
    assert.equal(tocLists[0].hidden, false);
    assert.equal(tocLists[1].hidden, true);

    // Filter pencarian dengan 1 hasil
    sidebar.querySelectorAll = () => [links[1]];
    initTutorialSidebar(doc, win);
    assert.equal(position.textContent, 'Topik 1 dari 1 yang ditampilkan');

    // Filter pencarian dengan 0 hasil
    sidebar.querySelectorAll = () => [];
    position.textContent = '0 topik ditampilkan';
    initTutorialSidebar(doc, win);
    assert.equal(position.textContent, '0 topik ditampilkan');
    JS;
    $process = new Process(['node', '--input-type=module'], base_path());
    $process->setInput($script)->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    foreach (['' => 19, 'XLSX' => 2, 'tidakadatopik123' => 0] as $query => $count) {
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
        'audit-saldo-dompet': 'audit-saldo', kategori: 'kategori', 'kolaborator-kas': 'kolaborasi',
        'tabungan-emas': 'emas', laporan: 'laporan', utangs: 'utang-piutang',
        piutangs: 'utang-piutang', langganans: 'langganan',
    };
    const ids = JSON.parse(readFileSync('resources/content/tutorial.json', 'utf8')).map(topic => topic.id);
    for (const [path, topic] of Object.entries(cases)) {
        assert.ok(ids.includes(topic));
        assert.equal(tutorialUrl(base, `${panel}/${path}?bulan=9#modal`, panel), `${base}#${topic}`);
    }
    for (const path of ['buku-kas', 'dompet', 'kategori', 'kolaborator-kas', 'tabungan-emas', 'akun-saya']) {
        assert.equal(tutorialUrl(base, `${panel}/pengaturan/${path}`, panel), `${base}#${cases[path]}`);
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

    expect($response->getContent())->toContain('--accent:#0f766e', 'background:#0f766e', '--accent:#5eead4');
    $topics = $response->viewData('topics');

    expect($topics)->toHaveCount(19);
    expect(array_column($topics, 'id'))->toBe([
        'akun', 'pengaturan-awal', 'dashboard', 'transaksi', 'transfer', 'pencarian',
        'import', 'kas', 'kolaborasi', 'kas-publik', 'dompet', 'audit-saldo',
        'kategori', 'emas', 'laporan', 'utang-piutang', 'langganan', 'trial-premium', 'profil-notifikasi',
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
        // Hanya hitung tautan daftar isi yang terpilih, abaikan tautan pager Sebelumnya/Berikutnya.
        expect($xpath->query('//a[@data-topic-link and @href="#'.$topic['id'].'"]'))->toHaveCount(1);
        expect($xpath->query('//article[@id="'.$topic['id'].'"]/ol/li'))->toHaveCount(count($topic['steps']));
        // Pastikan artikel hanya muncul pada bab halamannya yang terpilih.
        expect($xpath->query('//main/section[@class="chapter"]/h2[text()="'.$topic['page'].'"]/../article[@id="'.$topic['id'].'"]'))->toHaveCount(1);
    }
    // Pastikan pager hanya menautkan ke topik yang sedang ditampilkan.
    $idTampil = array_column($topics, 'id');
    foreach ($xpath->query('//nav[@aria-label="Navigasi topik"]/a') as $tautanPager) {
        expect($idTampil)->toContain(ltrim($tautanPager->getAttribute('href'), '#'));
    }
    expect($xpath->query('//aside//div[@class="nav-group"]'))->toHaveCount(count($response->viewData('groups')));
    expect($xpath->query('//main/section[@class="chapter"]'))->toHaveCount(count($response->viewData('groups')));
    $this->assertGuest();
});

it('tutorial mengelompokkan topik menurut halaman fitur dan menyembunyikan kelompok tanpa hasil', function () {
    $response = $this->get(route('tutorial', ['q' => 'Viewer']))->assertOk();
    $groups = $response->viewData('groups');

    expect($groups->keys()->all())->toBe(['Kategori', 'Kas', 'Langganan Premium']);
    expect(array_column($response->viewData('topics'), 'id'))->toBe(['kategori', 'kolaborasi', 'kas-publik', 'langganan']);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//aside//div[@class="nav-group"]'))->toHaveCount(3);
    expect($xpath->query('//main/section[@class="chapter"]'))->toHaveCount(3);
    // Hanya hitung tautan daftar isi yang terpilih di dalam sidebar (aside > details > nav).
    expect($xpath->query('//aside//a[@data-topic-link]'))->toHaveCount(4);
    // Pastikan hanya topik terpilih yang dirender sebagai artikel.
    expect($xpath->query('//main//article'))->toHaveCount(4);
});

it('tutorial mencari isi panduan tanpa membedakan kapital dan mengabaikan spasi tepi', function () {
    $response = $this->get(route('tutorial', ['q' => '  XLSX  ']))->assertOk();

    expect(array_column($response->viewData('topics'), 'id'))->toBe(['import', 'laporan']);
    expect($response->viewData('query'))->toBe('XLSX');
    expect($response->viewData('total'))->toBe(19);
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
    expect($item->shouldOpenUrlInNewTab())->toBeTrue();

    $this->actingAs(User::query()->notAdmin()->firstOrFail());
    expect($item->isVisible())->toBeTrue();
    $this->get(route('tutorial'))->assertOk();

    $this->actingAs(User::query()->admin()->firstOrFail());
    expect($item->isVisible())->toBeFalse();
});
