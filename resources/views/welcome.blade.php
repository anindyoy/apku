<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="APKu membantu mencatat transaksi, memantau kas dan dompet, serta memahami kondisi keuangan pribadi dalam satu tempat.">
    <title>APKu — Catat uang, pahami keuangan</title>
    <link rel="icon" href="{{ asset('logo-options/apku-dompet.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-5 py-4 sm:px-8" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" aria-label="APKu, beranda"><img src="{{ asset('logo-options/apku-dompet-wordmark.svg') }}" alt="APKu" class="h-10 w-auto sm:h-12"></a>
            <div class="flex items-center gap-2 sm:gap-5">
                <a href="#fitur" class="hidden text-sm font-semibold text-slate-600 hover:text-teal-700 sm:inline">Fitur</a>
                <a href="#akun" class="hidden text-sm font-semibold text-slate-600 hover:text-teal-700 sm:inline">Pilihan akun</a>
                <a href="{{ route('tutorial') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-teal-700 md:inline">Tutorial</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-teal-800 hover:bg-teal-50">Masuk</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Daftar gratis</a>
            </div>
        </nav>
    </header>
    <main id="konten">
        <section class="bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-800 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:py-28">
                <div>
                    <span class="inline-flex rounded-full border border-teal-300/30 bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide text-teal-100">KEUANGAN PRIBADI JADI LEBIH JELAS</span>
                    <h1 class="mt-7 max-w-xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Catat uangmu.<br><span class="text-amber-300">Pahami arahnya.</span></h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-teal-50">APKu menyatukan catatan transaksi, kas, dompet, utang-piutang, dan laporan. Lihat kondisi keuanganmu tanpa berpindah-pindah catatan.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('filament.admin.auth.register') }}" class="rounded-xl bg-amber-300 px-6 py-3.5 font-bold text-teal-950 shadow-lg hover:bg-amber-200">Mulai gratis <span aria-hidden="true">→</span></a>
                        <a href="{{ route('tutorial') }}" class="rounded-xl border border-white/40 px-6 py-3.5 font-semibold text-white hover:bg-white/10">Lihat cara penggunaan</a>
                    </div>
                    <p class="mt-5 text-sm text-teal-100">Akun Reguler dapat memakai fitur inti dengan hingga 2 kas dan 2 dompet.</p>
                </div>
                <div class="mx-auto w-full max-w-lg rounded-3xl border border-white/20 bg-white p-5 text-slate-900 shadow-2xl sm:p-7" aria-label="Ilustrasi ringkasan keuangan APKu">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-5"><div><p class="text-sm font-semibold text-teal-700">Ringkasan keuangan</p><p class="mt-1 text-xl font-bold">Semua dalam satu tampilan</p></div><img src="{{ asset('logo-options/apku-dompet.svg') }}" alt="" class="h-12 w-12"></div>
                    <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-teal-50 p-4"><span class="text-xs font-semibold uppercase text-teal-700">Kas</span><p class="mt-3 text-lg font-bold">Terpantau</p><p class="mt-1 text-sm text-slate-600">Kelompokkan tujuan uang</p></div><div class="rounded-2xl bg-amber-50 p-4"><span class="text-xs font-semibold uppercase text-amber-800">Dompet</span><p class="mt-3 text-lg font-bold">Terkelola</p><p class="mt-1 text-sm text-slate-600">Pantau tempat uang disimpan</p></div></div>
                    <div class="mt-4 rounded-2xl border border-slate-200 p-4"><div class="flex items-center justify-between"><span class="font-semibold">Aktivitas terbaru</span><span class="text-xs text-slate-500">Contoh tampilan</span></div><div class="mt-4 flex justify-between border-t border-slate-100 pt-4 text-sm"><span>● Pemasukan</span><span class="font-semibold text-emerald-700">Uang masuk</span></div><div class="mt-3 flex justify-between border-t border-slate-100 pt-3 text-sm"><span>● Pengeluaran</span><span class="font-semibold text-rose-700">Uang keluar</span></div></div>
                </div>
            </div>
        </section>
        <section id="fitur" class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
            <div class="max-w-2xl"><p class="text-sm font-bold uppercase tracking-widest text-teal-700">Fitur APKu</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Dari catatan harian sampai gambaran besar</h2><p class="mt-4 text-slate-600">Bangun kebiasaan mencatat, lalu gunakan datanya untuk mengambil keputusan dengan lebih yakin.</p></div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['01', 'Transaksi lengkap', 'Catat pemasukan, pengeluaran, transfer antar-kas dan pemindahan antar-dompet. Cari, filter, atau impor transaksi dari CSV dan XLSX.'],
                    ['02', 'Kas & dompet', 'Atur kelompok keuangan dan tempat penyimpanan uang, pilih yang utama, serta cocokkan saldo melalui audit dompet.'],
                    ['03', 'Laporan siap pakai', 'Lihat ringkasan dan rincian menurut periode, kas, atau dompet. Ekspor laporan ke PDF maupun Excel.'],
                    ['04', 'Utang & piutang', 'Pantau nominal, pihak terkait, jatuh tempo, penambahan, pembayaran, dan riwayatnya.'],
                    ['05', 'Tabungan emas', 'Catat berat emas per kas dan lihat estimasi nilai berdasarkan harga buyback atau harga manual.'],
                    ['06', 'Kas bersama', 'Dengan Premium aktif, undang Viewer atau Editor ke kas, atau bagikan tautan publik dengan akses lihat saja.'],
                ] as [$number, $title, $description])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"><span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-sm font-bold text-teal-700">{{ $number }}</span><h3 class="mt-5 text-xl font-bold">{{ $title }}</h3><p class="mt-3 leading-relaxed text-slate-600">{{ $description }}</p></article>
                @endforeach
            </div>
        </section>
        <section id="akun" class="bg-white py-20"><div class="mx-auto max-w-7xl px-5 sm:px-8"><p class="text-sm font-bold uppercase tracking-widest text-teal-700">Pilihan akun</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Mulai dari yang kamu butuhkan</h2><div class="mt-10 grid gap-6 md:grid-cols-2"><div class="rounded-3xl border border-slate-200 bg-slate-50 p-7 sm:p-9"><h3 class="text-2xl font-bold">Reguler</h3><p class="mt-2 text-slate-600">Fitur inti untuk mengatur keuangan pribadi.</p><ul class="mt-7 space-y-3 text-slate-700"><li>✓ Hingga 2 kas dan 2 dompet</li><li>✓ Transaksi, impor, laporan dan ekspor</li><li>✓ Audit saldo, emas, utang dan piutang</li><li>✓ Dapat menerima akses kas bersama</li></ul></div><div class="rounded-3xl border-2 border-teal-700 bg-teal-50 p-7 shadow-xl shadow-teal-900/10 sm:p-9"><span class="rounded-full bg-teal-700 px-3 py-1 text-xs font-bold text-white">LEBIH FLEKSIBEL</span><h3 class="mt-4 text-2xl font-bold">Premium aktif</h3><p class="mt-2 text-slate-600">Untuk kebutuhan kas dan kolaborasi yang berkembang.</p><ul class="mt-7 space-y-3 text-slate-700"><li>✓ Semua fitur Reguler</li><li>✓ Tambah kas dan dompet tanpa batas kuota Reguler</li><li>✓ Undang kolaborator sebagai Viewer atau Editor</li><li>✓ Buat tautan kas publik dengan akses lihat saja</li></ul></div></div><p class="mt-5 text-sm text-slate-500">Paket dan metode pembayaran Premium dapat dilihat setelah masuk ke aplikasi.</p></div></section>
        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8"><div class="rounded-3xl bg-teal-900 px-7 py-12 text-center text-white sm:px-12"><h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Siap merapikan catatan keuangan?</h2><p class="mx-auto mt-4 max-w-xl text-teal-100">Buat akun, atur kas dan dompet pertama, lalu mulai catat transaksi sehari-hari.</p><div class="mt-8 flex flex-wrap justify-center gap-3"><a href="{{ route('filament.admin.auth.register') }}" class="rounded-xl bg-amber-300 px-6 py-3 font-bold text-teal-950 hover:bg-amber-200">Buat akun gratis</a><a href="{{ route('tutorial') }}" class="rounded-xl border border-white/40 px-6 py-3 font-semibold text-white hover:bg-white/10">Baca tutorial</a></div></div></section>
    </main>
    <footer class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-5 py-8 text-sm text-slate-600 sm:flex-row sm:items-center sm:px-8"><span>© {{ date('Y') }} APKu. Catat dan pantau keuangan pribadi.</span><div class="flex gap-5"><a href="{{ route('tutorial') }}" class="hover:text-teal-700">Tutorial</a><a href="{{ route('filament.admin.auth.login') }}" class="hover:text-teal-700">Masuk</a></div></div></footer>
</body>
</html>
