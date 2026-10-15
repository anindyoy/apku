<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panduan penggunaan APKu: mulai dari pengaturan akun, transaksi, kas dan sumber dana hingga laporan dan langganan premium.">
    <title>Tutorial Penggunaan · APKu</title>
    {{-- Terapkan tema sebelum render untuk menghindari kedipan (flash) --}}
    <script>
        (function () {
            const saved = localStorage.getItem('apku-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme:dark)').matches;
            const dark = saved === 'dark' || (!saved && prefersDark);
            document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        })();
    </script>
    <style>
        /* ---------- Token warna ---------- */
        :root { color-scheme: light dark; --bg:#f7f8fa; --card:#fff; --text:#202b39; --muted:#526174; --line:#dde3ea; --accent:#0f766e; --soft:#f0fdfa; --nav-h:64px; font-family:system-ui,sans-serif; color:var(--text); background:var(--bg); }
        /* Mode gelap otomatis (sistem) */
        @media(prefers-color-scheme:dark) { :root:not([data-theme="light"]) { --bg:#141b24; --card:#1d2733; --text:#edf0f5; --muted:#b6c2d1; --line:#354254; --accent:#5eead4; --soft:#123b3a; } }
        /* Mode gelap manual (data-theme="dark") */
        [data-theme="dark"] { --bg:#141b24; --card:#1d2733; --text:#edf0f5; --muted:#b6c2d1; --line:#354254; --accent:#5eead4; --soft:#123b3a; color-scheme: dark; }
        /* Mode terang manual (data-theme="light") */
        [data-theme="light"] { --bg:#f7f8fa; --card:#fff; --text:#202b39; --muted:#526174; --line:#dde3ea; --accent:#0f766e; --soft:#f0fdfa; color-scheme: light; }

        /* ---------- Dasar ---------- */
        * { box-sizing:border-box; }
        @media(prefers-reduced-motion:no-preference) { html { scroll-behavior:smooth; } }
        body { margin:0; }
        a { color:var(--accent); text-underline-offset:4px; }
        a:focus-visible, input:focus-visible, button:focus-visible, summary:focus-visible { outline:3px solid var(--accent); outline-offset:3px; border-radius:6px; }
        p { line-height:1.75; }
        .wrap { max-width:84rem; margin:auto; padding:0 24px; }
        .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
        [hidden] { display:none !important; }
        .skip { position:absolute; top:-100px; }
        .skip:focus { top:8px; left:8px; z-index:50; padding:12px; background:var(--card); }

        /* ---------- Navbar atas ---------- */
        .navbar { position:sticky; top:0; z-index:20; background:color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter:blur(10px); border-bottom:1px solid var(--line); }
        .navbar .wrap { display:flex; align-items:center; gap:16px; min-height:var(--nav-h); }
        .brand { font-size:21px; font-weight:850; text-decoration:none; color:var(--text); white-space:nowrap; }
        .brand span { color:var(--accent); }
        .brand small { margin-left:10px; padding-left:12px; border-left:1px solid var(--line); font-size:14px; font-weight:600; color:var(--muted); }
        .search { flex:1; max-width:26rem; margin-inline:auto; }
        .search-row { display:flex; gap:8px; }
        input { min-width:0; width:100%; padding:9px 14px; border:1px solid var(--line); border-radius:10px; font:inherit; font-size:14px; background:var(--card); color:var(--text); }
        .button { display:inline-block; border:1px solid var(--line); border-radius:10px; padding:9px 16px; background:var(--card); color:var(--text); font:inherit; font-size:14px; cursor:pointer; text-decoration:none; white-space:nowrap; }
        .button:hover { border-color:var(--accent); }
        .primary { background:#0f766e; color:#fff; border-color:#0f766e; }
        .theme-toggle { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-width:0; padding:9px 14px; }
        .theme-toggle svg { flex-shrink:0; }
        .form-error { margin:0 0 16px; padding:10px 14px; border-radius:10px; background:var(--soft); border:1px solid var(--accent); font-size:14px; }

        /* ---------- Layout 3 kolom ---------- */
        .layout { display:grid; grid-template-columns:16rem minmax(0,1fr) 13rem; gap:48px; align-items:start; padding-top:32px; }
        aside, .toc { position:sticky; top:calc(var(--nav-h) + 24px); max-height:calc(100vh - var(--nav-h) - 48px); overflow:auto; padding-bottom:24px; }

        /* ---------- Sidebar kiri ---------- */
        .mobile-nav > summary { display:none; }
        .topic-position { font-size:13px; color:var(--muted); line-height:1.6; margin:0 0 18px; }
        .nav-group { margin-bottom:22px; }
        .nav-title { margin:0 0 8px; font-size:13px; font-weight:700; color:var(--text); }
        .nav-group ul, .toc ul { list-style:none; margin:0; padding:0; border-left:1px solid var(--line); }
        .nav-group a, .toc a { display:block; margin-left:-1px; padding:6px 14px; border-left:1px solid transparent; font-size:14px; line-height:1.45; text-decoration:none; color:var(--muted); }
        .nav-group a:hover, .toc a:hover { color:var(--text); border-left-color:var(--muted); }
        aside a[aria-current="location"], .toc a[aria-current="location"] { color:var(--accent); border-left-color:var(--accent); font-weight:650; background:none; }

        /* ---------- Konten ---------- */
        main { min-width:0; max-width:48rem; }
        .eyebrow { font-size:12px; text-transform:uppercase; letter-spacing:.15em; font-weight:750; color:var(--accent); }
        h1 { font-size:clamp(28px,4.5vw,38px); line-height:1.2; letter-spacing:-.03em; margin:12px 0 14px; }
        .lead { color:var(--muted); font-size:17px; margin:0; }
        .count { color:var(--muted); font-size:14px; margin:20px 0 0; }
        .chapter { margin-top:32px; padding-top:32px; border-top:1px solid var(--line); scroll-margin-top:calc(var(--nav-h) + 16px); }
        .chapter > h2 { font-size:28px; line-height:1.25; letter-spacing:-.02em; margin:0 0 8px; }
        article { margin-top:36px; scroll-margin-top:calc(var(--nav-h) + 24px); overflow-wrap:anywhere; }
        .tag { display:block; margin-bottom:6px; font-size:12px; font-weight:750; letter-spacing:.08em; text-transform:uppercase; color:var(--accent); }
        h3 { font-size:22px; line-height:1.3; margin:0 0 10px; }
        article:target > h3 { color:var(--accent); }
        h4 { font-size:16px; margin:28px 0 8px; }
        article h4, .callout { scroll-margin-top:calc(var(--nav-h) + 24px); }
        .intro { color:var(--muted); margin:0; }
        ol { padding-left:24px; margin:0; }
        li { padding-left:7px; margin:10px 0; line-height:1.75; }
        li::marker { color:var(--accent); font-weight:800; }

        /* Callout */
        .callout { margin-top:20px; padding:14px 18px; border:1px solid var(--line); border-radius:12px; background:var(--card); }
        .callout strong { display:block; font-size:14px; }
        .callout p { margin:6px 0 0; font-size:15px; }
        .note { background:var(--soft); border-color:color-mix(in srgb, var(--accent) 40%, var(--line)); }
        .note strong { color:var(--accent); }

        /* Sebelumnya / berikutnya */
        .pager { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:28px; }
        .pager a { display:block; padding:12px 16px; border:1px solid var(--line); border-radius:12px; background:var(--card); text-decoration:none; color:var(--text); }
        .pager a:hover { border-color:var(--accent); }
        .pager small { display:block; font-size:12px; color:var(--muted); }
        .pager strong { display:block; font-size:15px; color:var(--accent); margin-top:2px; }
        .pager .next { grid-column:2; text-align:right; }

        .empty { margin-top:32px; }

        /* ---------- Daftar "Di halaman ini" ---------- */
        .toc-title { margin:0 0 8px; font-size:13px; font-weight:700; }
        .toc .top { display:inline-block; margin-top:16px; font-size:13px; }

        footer { margin-top:56px; padding:28px 0; border-top:1px solid var(--line); color:var(--muted); font-size:13px; }

        /* ---------- Tombol tema ---------- */
        /* Default (light): tampilkan ikon bulan, sembunyikan matahari */
        .icon-sun { display:none; }
        .icon-moon { display:block; }
        /* Dark mode: tampilkan ikon matahari, sembunyikan bulan */
        [data-theme="dark"] .icon-sun { display:block; }
        [data-theme="dark"] .icon-moon { display:none; }
        @media(prefers-color-scheme:dark) {
            :root:not([data-theme="light"]) .icon-sun { display:block; }
            :root:not([data-theme="light"]) .icon-moon { display:none; }
        }

        /* ---------- Responsif ---------- */
        @media(max-width:1279px) { .layout { grid-template-columns:16rem minmax(0,1fr); gap:40px; } .toc { display:none; } }
        @media(max-width:1023px) {
            .layout { grid-template-columns:1fr; gap:8px; padding-top:20px; }
            aside { position:static; max-height:none; overflow:visible; padding-bottom:0; }
            .mobile-nav { border:1px solid var(--line); border-radius:12px; background:var(--card); padding:0 16px; }
            .mobile-nav > summary { display:block; padding:14px 0; font-weight:700; cursor:pointer; }
            .mobile-nav[open] > summary { border-bottom:1px solid var(--line); margin-bottom:14px; }
            .mobile-nav .topic-position { margin-bottom:12px; }
        }
        @media(max-width:760px) {
            :root { --nav-h:0px; }
            .wrap { padding-inline:16px; }
            .navbar { position:static; }
            .navbar .wrap { flex-wrap:wrap; padding-block:12px; gap:10px; }
            .brand small { display:none; }
            .search { order:3; flex-basis:100%; max-width:none; margin:0; }
            .navbar .button.open-app { margin-left:auto; }
            .chapter > h2 { font-size:24px; }
            .pager { grid-template-columns:1fr; }
            .pager .next { grid-column:auto; }
        }
    </style>
</head>
<body>
@php
    $ordered = array_values($topics);
    $positions = array_flip(array_column($ordered, 'id'));
@endphp
<a class="skip" href="#panduan">Lewati ke panduan</a>

<header class="navbar">
    <div class="wrap" id="atas">
        <a class="brand" href="{{ url('/') }}">AP<span>Ku</span><small>Panduan</small></a>
        <form class="search" method="get" action="{{ route('tutorial') }}" role="search">
            <label class="sr-only" for="q">Apa yang ingin Anda pelajari?</label>
            <div class="search-row">
                <input id="q" type="search" name="q" maxlength="100" value="{{ $query }}" placeholder="Cari panduan: saldo awal, import, voucher">
                <button class="button primary" type="submit">Cari</button>
            </div>
        </form>
        <button class="button theme-toggle" id="theme-toggle" type="button" aria-label="Ganti tema tampilan" title="Ganti tema terang/gelap">
            {{-- Ikon matahari (tampil saat mode gelap, klik untuk ke terang) --}}
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
            {{-- Ikon bulan (tampil saat mode terang, klik untuk ke gelap) --}}
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        </button>
        <a class="button open-app" href="{{ url('/admin') }}">Buka aplikasi &rarr;</a>
    </div>
</header>

<div class="wrap">
    <div class="layout">
        {{-- Sidebar kiri --}}
        <aside aria-label="Daftar isi" data-tutorial-sidebar>
            <details class="mobile-nav" data-mobile-nav open>
                <summary>Daftar isi panduan</summary>
                <p class="topic-position" data-topic-position aria-live="polite" aria-atomic="true">{{ count($topics) }} topik ditampilkan</p>
                <nav aria-label="Topik panduan">
                    @foreach($groups as $page => $pageTopics)
                        <div class="nav-group">
                            <p class="nav-title">{{ $page }}</p>
                            <ul>
                                @foreach($pageTopics as $topic)
                                    <li><a href="#{{ $topic['id'] }}" data-topic-link>{{ $topic['title'] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </details>
        </aside>
        <script>
            (() => {
                const nav = document.querySelector('[data-mobile-nav]');
                const desktop = matchMedia('(min-width:1024px)');
                const sync = () => { if (nav) nav.open = desktop.matches; };
                sync();
                desktop.addEventListener('change', sync);
            })();
        </script>

        {{-- Konten utama --}}
        <main id="panduan">
            <header>
                <span class="eyebrow">Pusat panduan · {{ $total }} topik</span>
                <h1 id="judul">Kenali fiturnya.<br>Kelola keuangan dengan mudah.</h1>
                <p class="lead">Panduan langkah demi langkah untuk pengguna APKu. Mulai dari akun pertama hingga laporan keuangan dan kolaborasi kas. Dapat dibaca tanpa login.</p>
                <p class="count">
                    {{ count($topics) }} panduan
                    @if($query !== '')
                        untuk “{{ $query }}” · <a href="{{ route('tutorial') }}">Tampilkan semua</a>
                    @else
                        · Ikuti berurutan atau pilih topik yang dibutuhkan.
                    @endif
                </p>
            </header>

            @error('q')<p class="form-error" role="alert">{{ $message }}</p>@enderror

            @foreach($groups as $page => $pageTopics)
                <section class="chapter" id="bab-{{ \Illuminate\Support\Str::slug($page) }}" aria-labelledby="judul-bab-{{ $loop->index }}">
                    <h2 id="judul-bab-{{ $loop->index }}">{{ $page }}</h2>
                    @foreach($pageTopics as $topic)
                        @php
                            $prev = $ordered[$positions[$topic['id']] - 1] ?? null;
                            $next = $ordered[$positions[$topic['id']] + 1] ?? null;
                        @endphp
                        <article id="{{ $topic['id'] }}">
                            <span class="tag">{{ $topic['category'] }}</span>
                            <h3>{{ $topic['title'] }}</h3>
                            <p class="intro">{{ $topic['intro'] }}</p>
                            <h4 id="{{ $topic['id'] }}-langkah">Langkah penggunaan</h4>
                            <ol>@foreach($topic['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                            <div class="callout example" id="{{ $topic['id'] }}-contoh"><strong>Contoh penggunaan</strong><p>{{ $topic['example'] }}</p></div>
                            <div class="callout note" id="{{ $topic['id'] }}-catatan"><strong>Perlu diketahui</strong><p>{{ $topic['note'] }}</p></div>
                            @if($prev || $next)
                                <nav class="pager" aria-label="Navigasi topik">
                                    @if($prev)<a class="prev" href="#{{ $prev['id'] }}"><small>&larr; Sebelumnya</small><strong>{{ $prev['title'] }}</strong></a>@endif
                                    @if($next)<a class="next" href="#{{ $next['id'] }}"><small>Berikutnya &rarr;</small><strong>{{ $next['title'] }}</strong></a>@endif
                                </nav>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endforeach

            @if($groups->isEmpty())
                <article class="empty"><h3>Panduan belum ditemukan</h3><p>Coba kata lain seperti “transaksi”, “kas”, atau “laporan”.</p><a href="{{ route('tutorial') }}">Lihat seluruh panduan</a></article>
            @endif
        </main>

        {{-- Kolom kanan: Di halaman ini (diisi sesuai topik yang tampil, diatur oleh tutorial-sidebar.js) --}}
        <nav class="toc" aria-label="Di halaman ini" data-toc hidden>
            <p class="toc-title">Di halaman ini</p>
            @foreach($ordered as $topic)
                <ul data-toc-for="{{ $topic['id'] }}" hidden>
                    <li><a href="#{{ $topic['id'] }}-langkah">Langkah penggunaan</a></li>
                    <li><a href="#{{ $topic['id'] }}-contoh">Contoh penggunaan</a></li>
                    <li><a href="#{{ $topic['id'] }}-catatan">Perlu diketahui</a></li>
                </ul>
            @endforeach
            <a class="top" href="#atas">Kembali ke atas &uarr;</a>
        </nav>
    </div>

    <footer>APKu · Panduan pengguna Reguler dan Premium. Ketersediaan aksi mengikuti hak akses akun dan kas.</footer>
</div>

<script type="module" src="{{ asset('js/tutorial-sidebar.js') }}"></script>
<script>
    // Logika toggle tema terang/gelap dengan penyimpanan preferensi ke localStorage
    (function () {
        const KEY = 'apku-theme';
        const html = document.documentElement;
        const btn = document.getElementById('theme-toggle');
        if (!btn) return;

        const applyTheme = (theme) => {
            html.setAttribute('data-theme', theme);
            localStorage.setItem(KEY, theme);
            btn.setAttribute('aria-label', theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
            btn.setAttribute('title', theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
        };

        btn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme');
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });

        // Perbarui label tombol sesuai tema yang sudah diterapkan saat muat halaman
        const initial = html.getAttribute('data-theme') ?? 'light';
        btn.setAttribute('aria-label', initial === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
        btn.setAttribute('title', initial === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
    })();
</script>
</body>
</html>