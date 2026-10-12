<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="APKu membantu mencatat transaksi, memantau kas dan sumber dana, serta memahami kondisi keuangan pribadi dalam satu tempat.">
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
                <a href="#harga" class="hidden text-sm font-semibold text-slate-600 hover:text-teal-700 sm:inline">Harga</a>
                <a href="{{ route('tutorial') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-teal-700 md:inline">Tutorial</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-teal-800 hover:bg-teal-50">Masuk</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Daftar gratis</a>
            </div>
        </nav>
    </header>
    <main id="konten">
        <section class="bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-800 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-10 sm:px-8 lg:grid-cols-2 lg:py-28">
                <div>
                    <span class="inline-flex rounded-full border border-teal-300/30 bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide text-teal-100">KEUANGAN PRIBADI JADI LEBIH JELAS</span>
                    <h1 class="mt-7 max-w-xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Catat uangmu.<br><span class="text-amber-300">Pahami arahnya.</span></h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-teal-50">APKu menyatukan catatan transaksi, kas, sumber dana, utang-piutang, dan laporan. Lihat kondisi keuanganmu tanpa
                        berpindah-pindah catatan.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('filament.admin.auth.register') }}" class="rounded-xl bg-amber-300 px-6 py-3.5 font-bold text-teal-950 shadow-lg hover:bg-amber-200">Mulai gratis <span
                                aria-hidden="true">→</span></a>
                        <a href="{{ route('tutorial') }}" class="rounded-xl border border-white/40 px-6 py-3.5 font-semibold text-white hover:bg-white/10">Lihat cara penggunaan</a>
                    </div>
                    <p class="mt-5 text-sm text-teal-100">Akun Reguler dapat memakai fitur inti dengan hingga 2 kas dan 2 sumber dana. Akun baru yang memenuhi syarat dapat Coba Premium gratis {{ $trialDurasi ?? 30 }} hari.
                    </p>
                </div>
                <div class="mx-auto w-full max-w-lg rounded-3xl border border-white/20 bg-white p-5 text-slate-900 shadow-2xl sm:p-7" aria-label="Ilustrasi ringkasan keuangan APKu">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                        <div>
                            <p class="text-sm font-semibold text-teal-700">Ringkasan keuangan</p>
                            <p class="mt-1 text-xl font-bold">Semua dalam satu tampilan</p>
                        </div><img src="{{ asset('logo-options/apku-dompet.svg') }}" alt="" class="h-12 w-12">
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-teal-50 p-4"><span class="text-xs font-semibold uppercase text-teal-700">Kas</span>
                            <p class="mt-3 text-lg font-bold">Terpantau</p>
                            <p class="mt-1 text-sm text-slate-600">Kelompokkan tujuan uang</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 p-4"><span class="text-xs font-semibold uppercase text-amber-800">Sumber Dana</span>
                            <p class="mt-3 text-lg font-bold">Terkelola</p>
                            <p class="mt-1 text-sm text-slate-600">Pantau tempat uang disimpan</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between"><span class="font-semibold">Transaksi terbaru</span><span class="text-xs text-slate-500">Contoh tampilan</span></div>
                        <div class="mt-4 flex justify-between border-t border-slate-100 pt-4 text-sm"><span>● Pemasukan</span><span class="font-semibold text-emerald-700">Uang masuk</span></div>
                        <div class="mt-3 flex justify-between border-t border-slate-100 pt-3 text-sm"><span>● Pengeluaran</span><span class="font-semibold text-rose-700">Uang keluar</span></div>
                    </div>
                </div>
            </div>
        </section>
        <section id="fitur" class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-widest text-teal-700">Fitur APKu</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Dari catatan harian sampai gambaran besar</h2>
                <p class="mt-4 text-slate-600">Bangun kebiasaan mencatat, lalu gunakan datanya untuk mengambil keputusan dengan lebih yakin.</p>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ([['01', 'Transaksi lengkap', 'Catat pemasukan, pengeluaran, transfer antar-kas dan pemindahan antar-sumber dana. Cari, filter, atau impor transaksi dari CSV dan XLSX.'], ['02', 'Kas & sumber dana', 'Atur kelompok keuangan dan tempat penyimpanan uang, pilih yang utama, serta cocokkan saldo dengan kondisi uang sebenarnya.'], ['03', 'Laporan siap pakai', 'Lihat ringkasan dan rincian menurut periode, kas, atau sumber dana. Ekspor laporan ke PDF maupun Excel.'], ['04', 'Utang & piutang', 'Pantau nominal, pihak terkait, jatuh tempo, penambahan, pembayaran, dan riwayatnya.'], ['05', 'Tabungan emas', 'Catat berat emas per kas dan lihat estimasi nilai berdasarkan harga buyback terakhir.'], ['06', 'Kas bersama', 'Dengan Premium aktif, undang Viewer atau Editor ke kas, atau bagikan tautan publik dengan akses lihat saja.']] as [$number, $title, $description])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"><span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-sm font-bold text-teal-700">{{ $number }}</span>
                        <h3 class="mt-5 text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-3 leading-relaxed text-slate-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </section>
        <section id="akun" class="bg-white py-10">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <p class="text-sm font-bold uppercase tracking-widest text-teal-700">Pilihan akun</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Mulai dari yang kamu butuhkan</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-7 sm:p-9">
                        <h3 class="text-2xl font-bold">Reguler</h3>
                        <p class="mt-2 text-slate-600">Fitur inti untuk mengatur keuangan pribadi.</p>
                        <ul class="mt-7 space-y-3 text-slate-700">
                            <li>✓ Hingga 2 kas dan 2 sumber dana</li>
                            <li>✓ Transaksi, impor, laporan dan ekspor</li>
                            <li>✓ Cocokkan saldo, catat emas, catat utang dan piutang</li>
                            <li>✓ Dapat menerima akses kas bersama</li>
                        </ul>
                    </div>
                    <div class="rounded-3xl border-2 border-teal-700 bg-teal-50 p-7 shadow-xl shadow-teal-900/10 sm:p-9"><span
                            class="rounded-full bg-teal-700 px-3 py-1 text-xs font-bold text-white">LEBIH FLEKSIBEL</span>
                        <h3 class="mt-4 text-2xl font-bold">Premium aktif</h3>
                        <p class="mt-2 text-slate-600">Untuk kebutuhan kas dan kolaborasi yang berkembang. Tersedia Coba Premium gratis {{ $trialDurasi ?? 30 }} hari untuk akun baru yang memenuhi syarat.</p>
                        <ul class="mt-7 space-y-3 text-slate-700">
                            <li>✓ Semua fitur Reguler</li>
                            <li>✓ Coba Premium gratis {{ $trialDurasi ?? 30 }} hari, sekali per akun</li>
                            <li>✓ Tambah kas dan sumber dana tanpa batas kuota Reguler</li>
                            <li>✓ Undang kolaborator sebagai Viewer atau Editor</li>
                            <li>✓ Buat tautan kas publik dengan akses lihat saja</li>
                        </ul>
                        @if ($trialAktif ?? true)
                            <div class="mt-7 flex flex-wrap items-center gap-3">
                                <a href="{{ auth()->check() ? \App\Filament\Resources\LanggananResource::getUrl('index') : route('filament.admin.auth.register') }}"
                                    data-coba-premium class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-800">Coba Premium gratis {{ $trialDurasi ?? 30 }} hari</a>
                            </div>
                            <p class="mt-3 text-xs leading-relaxed text-slate-500">
                                @auth
                                    Buka halaman Langganan lalu pilih Mulai coba gratis pada kartu trial. Trial sekali per akun, nomor HP, dan email.
                                @else
                                    Daftar lalu verifikasi email dan isi nomor HP untuk memulai trial dari Dashboard atau Langganan. Trial sekali per akun, nomor HP, dan email.
                                @endauth
                            </p>
                        @endif
                    </div>
                </div>
                <p class="mt-5 text-sm text-slate-500">Harga paket Premium terbaru tampil pada daftar di bawah dan diperbarui otomatis dari data admin. Metode pembayaran tersedia setelah masuk ke
                    aplikasi. Trial gratis dimulai dari Dashboard atau Langganan setelah email terverifikasi dan nomor HP terisi.</p>
            </div>
        </section>
        <section id="harga" class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-widest text-teal-700">Harga Premium</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Pilih durasi sesuai kebutuhan</h2>
                <p class="mt-4 text-slate-600">Semua paket membuka fitur Premium yang sama, hanya durasinya yang berbeda. Harga di bawah diambil langsung dari paket aktif di aplikasi.</p>
            </div>
            @if (session('paket_gagal'))
                <p class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900" role="alert">{{ session('paket_gagal') }}</p>
            @endif
            @php
                // Daftar harga di halaman depan memakai data yang sama dengan halaman langganan.
                $daftarHarga = $paketHemat ?? collect();
            @endphp
            @if ($daftarHarga->isNotEmpty())
                @php
                    // Paket terbaik dipilih lebih dulu; jika tidak ada, pakai paket pertama.
                    $indeksTerpilih = $daftarHarga->values()->search(fn($p) => (bool) ($p['is_terbaik'] ?? false));
                    $indeksTerpilih = $indeksTerpilih === false ? 0 : $indeksTerpilih;
                @endphp

                {{-- Pilihan paket memakai radio + CSS saja (tanpa JavaScript). --}}
                <style>
                    @foreach ($daftarHarga->values() as $i => $paket)
                        #paket-{{ $i }}:checked~[data-ringkasan="{{ $i }}"] {
                            display: flex;
                        }

                        #paket-{{ $i }}:checked~label[for="paket-{{ $i }}"] {
                            background-color: #f0fdfa;
                            box-shadow: inset 4px 0 0 #0f766e;
                        }

                        #paket-{{ $i }}:checked~label[for="paket-{{ $i }}"] .dot {
                            border-color: #0f766e;
                            background-color: #0f766e;
                            box-shadow: inset 0 0 0 3px #fff;
                        }

                        #paket-{{ $i }}:focus-visible~label[for="paket-{{ $i }}"] {
                            outline: 2px solid #0f766e;
                            outline-offset: -2px;
                        }
                    @endforeach
                </style>

                <div class="mt-8 grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]" data-daftar-harga-paket>
                    {{-- Tabel paket: radio, baris paket, dan bar ringkasan harus bersaudara (satu parent). --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($daftarHarga->values() as $i => $paket)
                            <input type="radio" name="paket-harga" id="paket-{{ $i }}" class="sr-only" @checked($i === $indeksTerpilih)>
                        @endforeach

                        {{-- Judul kolom --}}
                        <div class="hidden items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-500 sm:flex">
                            <span class="flex-1 pl-8">Paket</span>
                            <span class="w-28 text-right">Per bulan</span>
                            <span class="w-28 text-right">Total</span>
                        </div>

                        @foreach ($daftarHarga->values() as $i => $paket)
                            @php
                                $adaHemat = ($paket['hemat_bulan'] ?? 0) > 0;
                                $isTerbaik = (bool) ($paket['is_terbaik'] ?? false);
                            @endphp
                            <label for="paket-{{ $i }}" data-harga-paket="{{ $paket['label'] }}"
                                class="flex cursor-pointer items-center gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-teal-50/50">
                                <span class="dot h-5 w-5 shrink-0 rounded-full border-2 border-slate-300 bg-white transition" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-bold">{{ $paket['label'] }}</span>
                                        @if ($isTerbaik)
                                            <span class="rounded-full bg-amber-300 px-2 py-0.5 text-[11px] font-bold leading-4 text-teal-950">Paling hemat</span>
                                        @endif
                                        @if ($adaHemat)
                                            <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[11px] font-bold leading-4 text-teal-800">Diskon {{ $paket['persen'] }}%</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-slate-500 sm:hidden">Rp{{ number_format(round($paket['per_bulan']), 0, ',', '.') }} / bulan</span>
                                </span>
                                <span class="hidden w-28 text-right text-sm text-slate-500 sm:block">Rp{{ number_format(round($paket['per_bulan']), 0, ',', '.') }}</span>
                                <span class="w-28 text-right text-lg font-bold tracking-tight">Rp{{ number_format($paket['harga'], 0, ',', '.') }}</span>
                            </label>
                        @endforeach

                        {{-- Bar ringkasan paket terpilih (hanya satu yang tampil) --}}
                        @foreach ($daftarHarga->values() as $i => $paket)
                            @php
                                $adaHemat = ($paket['hemat_bulan'] ?? 0) > 0;
                            @endphp
                            <div data-ringkasan="{{ $i }}"
                                class="hidden flex-col gap-3 bg-gradient-to-r from-teal-900 to-teal-800 px-4 py-4 text-white sm:flex-row sm:items-center sm:justify-between" aria-live="polite">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-widest text-teal-200">Paket terpilih</p>
                                    <p class="mt-0.5 flex flex-wrap items-baseline gap-x-2">
                                        <span class="text-lg font-bold">{{ $paket['label'] }}</span>
                                        <span class="text-2xl font-bold tracking-tight text-amber-300">Rp{{ number_format($paket['harga'], 0, ',', '.') }}</span>
                                    </p>
                                    <p class="text-xs {{ $adaHemat ? 'font-semibold text-amber-200' : 'text-teal-100' }}">
                                        @if ($adaHemat)
                                            Hemat Rp{{ number_format(round($paket['hemat_bulan']), 0, ',', '.') }} / bulan (diskon {{ $paket['persen'] }}%) dibanding paket 1 bulan
                                        @else
                                            Harga acuan per bulan
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('beli-paket', $paket['id']) }}" data-beli-paket="{{ $paket['id'] }}"
                                    class="shrink-0 rounded-xl bg-amber-300 px-5 py-2.5 text-center text-sm font-bold text-teal-950 hover:bg-amber-200">@auth Beli paket @else Daftar untuk membeli @endauth</a>
                            </div>
                        @endforeach
                    </div>

                    {{-- Benefit sama untuk semua paket, ditampilkan sekali di luar tabel. --}}
                    <div class="space-y-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <p class="text-xs font-bold uppercase tracking-widest text-teal-700">Semua paket sudah termasuk</p>
                            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                <li class="flex gap-2"><span class="font-bold text-teal-700" aria-hidden="true">✓</span> Semua fitur Reguler</li>
                                <li class="flex gap-2"><span class="font-bold text-teal-700" aria-hidden="true">✓</span> Kas dan sumber dana tanpa batas kuota Reguler</li>
                                <li class="flex gap-2"><span class="font-bold text-teal-700" aria-hidden="true">✓</span> Undang kolaborator sebagai Viewer atau Editor</li>
                                <li class="flex gap-2"><span class="font-bold text-teal-700" aria-hidden="true">✓</span> Tautan kas publik dengan akses lihat saja</li>
                            </ul>
                        </div>
                        @auth
                            <p class="px-1 text-xs leading-relaxed text-slate-500">Pilih paket lalu selesaikan order di halaman Langganan untuk memilih metode pembayaran dan memakai voucher. Total final dihitung ulang
                                di server saat order dibuat.</p>
                        @else
                            <p class="px-1 text-xs leading-relaxed text-slate-500">Daftar lalu masuk ke aplikasi untuk memilih metode pembayaran, memakai voucher, dan menyelesaikan order. Total final dihitung ulang
                                di server saat order dibuat.</p>
                        @endauth
                    </div>
                </div>
            @else
                <p class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 text-slate-600" role="status">Belum ada paket Premium yang tersedia. Silakan daftar dahulu, harga terbaru akan
                    tampil di halaman Langganan setelah masuk.</p>
            @endif
        </section>
        <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
            <div class="rounded-3xl bg-teal-900 px-7 py-12 text-center text-white sm:px-12">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Siap merapikan catatan keuangan?</h2>
                <p class="mx-auto mt-4 max-w-xl text-teal-100">Buat akun, atur kas dan sumber dana pertama, lalu mulai catat transaksi sehari-hari.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3"><a href="{{ route('filament.admin.auth.register') }}"
                        class="rounded-xl bg-amber-300 px-6 py-3 font-bold text-teal-950 hover:bg-amber-200">Buat akun gratis</a><a href="{{ route('tutorial') }}"
                        class="rounded-xl border border-white/40 px-6 py-3 font-semibold text-white hover:bg-white/10">Lihat tutorial</a></div>
            </div>
        </section>
    </main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-5 py-8 text-sm text-slate-600 sm:flex-row sm:items-center sm:px-8"><span>© {{ date('Y') }} APKu. Catat dan pantau
                keuangan pribadi.</span>
            <div class="flex gap-5"><a href="{{ route('tutorial') }}" class="hover:text-teal-700">Tutorial</a><a href="{{ route('filament.admin.auth.login') }}"
                    class="hover:text-teal-700">Masuk</a></div>
        </div>
    </footer>
</body>

</html>
