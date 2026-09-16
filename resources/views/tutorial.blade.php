<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panduan penggunaan APKu: mulai dari pengaturan akun, transaksi, kas dan dompet hingga laporan dan langganan premium.">
    <title>Tutorial Penggunaan · APKu</title>
    <style>
        :root { color-scheme: light dark; --bg:#f7f8fa; --card:#fff; --text:#202b39; --muted:#526174; --line:#dde3ea; --accent:#875300; --soft:#fff3d8; font-family:system-ui,sans-serif; color:var(--text); background:var(--bg); }
        * { box-sizing:border-box; } body { margin:0; } a { color:var(--accent); text-underline-offset:4px; } a:focus-visible, input:focus-visible, button:focus-visible { outline:3px solid #d89413; outline-offset:4px; }
        .wrap { max-width:1200px; margin:auto; padding:0 24px; } .topbar { display:flex; justify-content:space-between; align-items:center; padding-block:22px; gap:16px; } .brand { font-size:23px; font-weight:850; text-decoration:none; color:var(--text); } .brand span { color:var(--accent); } .button { display:inline-block; border:1px solid var(--line); border-radius:10px; padding:11px 18px; background:var(--card); color:var(--text); font:inherit; cursor:pointer; text-decoration:none; }
        .hero { background:var(--soft); border:1px solid var(--line); border-radius:24px; padding:40px; margin:12px 0 30px; } .eyebrow { font-size:12px; text-transform:uppercase; letter-spacing:.15em; font-weight:750; color:var(--accent); } h1 { font-size:clamp(30px,5vw,48px); line-height:1.15; letter-spacing:-.035em; margin:14px 0; max-width:700px; } p { line-height:1.75; } .hero p { max-width:680px; color:var(--muted); } .search { margin-top:24px; max-width:660px; } label { display:block; font-weight:650; margin-bottom:8px; } .search-row { display:flex; gap:10px; } input { min-width:0; width:100%; padding:14px; border:1px solid var(--line); border-radius:10px; font:inherit; background:var(--card); color:var(--text); } .primary { background:#825000; color:#fff; border-color:#825000; }
        .layout { display:grid; grid-template-columns:240px minmax(0,1fr); gap:32px; align-items:start; } aside { position:sticky; top:20px; max-height:calc(100vh - 40px); overflow:auto; padding-bottom:16px; } aside h2 { font-size:14px; } aside a { display:block; padding:9px 10px; border-radius:8px; font-size:14px; text-decoration:none; color:var(--muted); } aside a:hover { background:var(--soft); color:var(--accent); } .count { color:var(--muted); font-size:14px; margin:0 0 18px; }
        .page-group { margin:18px 0 6px; } .page-group h3 { margin:0 0 5px; padding:0 10px; color:var(--text); font-size:13px; } .chapter { border:1px solid var(--line); border-radius:18px; margin:0 0 16px; padding:0 18px; background:var(--card); } .chapter > summary { cursor:pointer; padding:20px 4px; font-size:20px; font-weight:750; list-style:none; } .chapter > summary::-webkit-details-marker { display:none; } .chapter > summary::after { content:'+'; float:right; color:var(--accent); } .chapter[open] > summary::after { content:'−'; } .chapter > summary:focus-visible { outline:3px solid #d89413; outline-offset:4px; } .chapter article { margin-bottom:18px; }
        article { background:var(--card); border:1px solid var(--line); border-radius:18px; padding:30px; margin-bottom:22px; scroll-margin-top:24px; overflow-wrap:anywhere; } .tag { display:inline-block; color:var(--accent); background:var(--soft); padding:5px 10px; font-size:12px; border-radius:20px; font-weight:700; } h2 { font-size:25px; line-height:1.3; margin:14px 0; } h3 { font-size:15px; margin-top:24px; } .intro { color:var(--muted); } ol { padding-left:24px; } li { padding-left:7px; margin:12px 0; line-height:1.75; } li::marker { color:var(--accent); font-weight:800; } .example,.note { padding:16px 20px; border-radius:10px; margin-top:18px; } .example { background:var(--bg); border:1px solid var(--line); } .note { background:var(--soft); border-left:3px solid #d89413; } .example p,.note p { margin:5px 0 0; font-size:14px; } .back { display:inline-block; margin-top:20px; font-size:13px; } footer { padding:30px 0; color:var(--muted); font-size:13px; } .skip { position:absolute; top:-100px; } .skip:focus { top:8px; padding:12px; background:var(--card); }
        @media(max-width:760px) { .wrap { padding-inline:16px; } .hero { padding:26px 22px; } .layout { grid-template-columns:1fr; gap:16px; } aside { position:static; max-height:none; } aside nav { display:flex; flex-wrap:wrap; gap:4px; } aside a { border:1px solid var(--line); } article { padding:22px; } .search-row { flex-wrap:wrap; } }
        @media(prefers-color-scheme:dark) { :root { --bg:#141b24; --card:#1d2733; --text:#edf0f5; --muted:#b6c2d1; --line:#354254; --accent:#f7c96b; --soft:#332b1c; } }
        article:target { border-color:#d89413; box-shadow:0 0 0 2px #d89413; }
        aside a[aria-current="location"] { background:var(--soft); color:var(--accent); font-weight:750; border-left:3px solid #d89413; }
        aside a[aria-current="location"]::after { content:"Sedang dibaca"; display:block; font-size:11px; margin-top:4px; }
        .topic-position { font-size:13px; color:var(--muted); line-height:1.6; }
    </style>
</head>
<body>
<a class="skip" href="#panduan">Lewati ke panduan</a>
<div class="wrap" id="atas">
    <header class="topbar"><a class="brand" href="{{ url('/') }}">AP<span>Ku</span></a><a class="button" href="{{ url('/admin') }}">Buka aplikasi &rarr;</a></header>
    <section class="hero" aria-labelledby="judul">
        <span class="eyebrow">Pusat panduan · {{ $total }} topik</span>
        <h1 id="judul">Kenali fiturnya.<br>Kelola keuangan dengan mudah.</h1>
        <p>Panduan langkah demi langkah untuk pengguna APKu. Mulai dari akun pertama hingga laporan keuangan dan kolaborasi kas. Dapat dibaca tanpa login.</p>
        <form class="search" method="get" action="{{ route('tutorial') }}">
            <label for="q">Apa yang ingin Anda pelajari?</label>
            <div class="search-row"><input id="q" type="search" name="q" maxlength="100" value="{{ $query }}" placeholder="Contoh: saldo awal, import, voucher"><button class="button primary" type="submit">Cari panduan</button></div>
            @error('q')<p role="alert">{{ $message }}</p>@enderror
        </form>
    </section>
    <div class="layout">
        <aside aria-label="Daftar isi" data-tutorial-sidebar>
            <h2>Jelajahi panduan</h2>
            <p class="topic-position" data-topic-position aria-live="polite" aria-atomic="true">{{ count($topics) }} topik ditampilkan</p>
            <nav>
                @foreach($groups as $page => $pageTopics)
                    <div class="page-group">
                        <h3>{{ $page }}</h3>
                        @foreach($pageTopics as $topic)
                            <a href="#{{ $topic['id'] }}" data-topic-link>{{ $topic['title'] }}</a>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </aside>
        <main id="panduan">
            <p class="count">
                {{ count($topics) }} panduan
                @if($query !== '')
                    untuk “{{ $query }}” · <a href="{{ route('tutorial') }}">Tampilkan semua</a>
                @else
                    · Ikuti berurutan atau pilih topik yang dibutuhkan.
                @endif
            </p>
            @foreach($groups as $page => $pageTopics)
                <details class="chapter" data-tutorial-chapter @if($loop->first) open @endif>
                    <summary>{{ $page }}</summary>
                    @foreach($pageTopics as $topic)
                        <article id="{{ $topic['id'] }}">
                            <span class="tag">{{ $topic['category'] }}</span>
                            <h2>{{ $topic['title'] }}</h2>
                            <p class="intro">{{ $topic['intro'] }}</p>
                            <h3>Langkah penggunaan</h3>
                            <ol>@foreach($topic['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                            <div class="example"><strong>Contoh penggunaan</strong><p>{{ $topic['example'] }}</p></div>
                            <div class="note"><strong>Perlu diketahui</strong><p>{{ $topic['note'] }}</p></div>
                            <a class="back" href="#atas">Kembali ke atas &uarr;</a>
                        </article>
                    @endforeach
                </details>
            @endforeach
            @if($groups->isEmpty())
                <article><h2>Panduan belum ditemukan</h2><p>Coba kata lain seperti “transaksi”, “kas”, atau “laporan”.</p><a href="{{ route('tutorial') }}">Lihat seluruh panduan</a></article>
            @endif
        </main>
    </div>
    <footer>APKu · Panduan pengguna Reguler dan Premium. Ketersediaan aksi mengikuti hak akses akun dan kas.</footer>
</div>
<script type="module" src="{{ asset('js/tutorial-sidebar.js') }}"></script>
</body>
</html>
