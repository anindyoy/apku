<x-filament-panels::page>
    @php
        $laporan = $this->dataLaporan;
        $formatRupiah = fn ($nominal) => 'Rp '.number_format($nominal, 0, ',', '.');
        $totalGrafik = max(1, $laporan['pemasukan'], $laporan['pengeluaran']);
        $tinggiPemasukan = $laporan['pemasukan'] ? max(8, ($laporan['pemasukan'] / $totalGrafik) * 100) : 0;
        $tinggiPengeluaran = $laporan['pengeluaran'] ? max(8, ($laporan['pengeluaran'] / $totalGrafik) * 100) : 0;
        $buatGradien = function ($kategori) {
            if (! count($kategori)) return 'conic-gradient(#e5e7eb 0 100%)';
            $posisi = 0;
            $bagian = [];
            foreach ($kategori as $item) {
                $akhir = $posisi + $item['persen'];
                $bagian[] = "{$item['warna']} {$posisi}% {$akhir}%";
                $posisi = $akhir;
            }
            return 'conic-gradient('.implode(', ', $bagian).')';
        };
    @endphp

    <div class="laporan-page">
        <section class="laporan-filter">
            <div class="laporan-filter__book">
                <label for="buku-kas">Buku kas</label>
                <select id="buku-kas" wire:model.live="bukuKasId">
                    <option value="semua">Semua Buku Kas</option>
                    @foreach (\App\Models\BukuKas::all() as $buku)
                        <option value="{{ $buku->id }}">{{ $buku->nama_buku }}</option>
                    @endforeach
                </select>
            </div>
            <div class="laporan-periods" aria-label="Pilihan periode laporan">
                @foreach (['harian' => 'Harian', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'custom' => 'Custom'] as $nilai => $label)
                    <button type="button" wire:click="pilihPeriode('{{ $nilai }}')" @class(['active' => $periode === $nilai])>{{ $label }}</button>
                @endforeach
            </div>
        </section>

        <section class="laporan-datebar">
            @if ($periode !== 'custom')
                <button type="button" wire:click="geserPeriode(-1)" aria-label="Periode sebelumnya">&#10094;</button>
                <input type="date" wire:model.live="tanggalAcuan" aria-label="Tanggal acuan">
                <strong>{{ $laporan['label'] }}</strong>
                <button type="button" wire:click="geserPeriode(1)" aria-label="Periode berikutnya">&#10095;</button>
            @else
                <div class="laporan-range">
                    <label>Dari <input type="date" wire:model.live.debounce.400ms="tanggalMulai"></label>
                    <span>hingga</span>
                    <label>Sampai <input type="date" wire:model.live.debounce.400ms="tanggalSelesai"></label>
                </div>
                <strong>{{ $laporan['label'] }}</strong>
            @endif
        </section>

        <section class="laporan-card laporan-summary">
            <header><x-heroicon-o-book-open /> <h2>{{ $bukuKasId === 'semua' ? 'Semua Buku Kas' : optional(\App\Models\BukuKas::find($bukuKasId))->nama_buku }}</h2></header>
            <div class="laporan-summary__content">
                <div class="laporan-totals">
                    <div><span>Saldo awal periode</span><strong>{{ $formatRupiah($laporan['saldoAwal']) }}</strong></div>
                    <div class="income"><span>Semua pemasukan</span><strong>+ {{ $formatRupiah($laporan['pemasukan']) }}</strong></div>
                    <div class="expense"><span>Semua pengeluaran</span><strong>− {{ $formatRupiah($laporan['pengeluaran']) }}</strong></div>
                    <div class="accumulation"><span>Akumulasi</span><strong>{{ $laporan['akumulasi'] >= 0 ? '+' : '−' }} {{ $formatRupiah(abs($laporan['akumulasi'])) }}</strong></div>
                    <div class="ending"><span>Saldo akhir periode</span><strong>{{ $formatRupiah($laporan['saldoAkhir']) }}</strong></div>
                </div>
                <div class="bar-chart" aria-label="Perbandingan pemasukan dan pengeluaran">
                    <div class="bar-chart__plot">
                        <div class="bar income-bar" style="height: {{ $tinggiPemasukan }}%"><span>{{ $formatRupiah($laporan['pemasukan']) }}</span></div>
                        <div class="bar expense-bar" style="height: {{ $tinggiPengeluaran }}%"><span>{{ $formatRupiah($laporan['pengeluaran']) }}</span></div>
                    </div>
                    <div class="bar-chart__labels"><span>Pemasukan</span><span>Pengeluaran</span></div>
                </div>
            </div>
        </section>

        <div class="laporan-categories">
            @foreach ([['Pengeluaran', 'kategoriPengeluaran', 'expense'], ['Pemasukan', 'kategoriPemasukan', 'income']] as [$judul, $key, $kelas])
                <section class="laporan-card category-card {{ $kelas }}">
                    <header>
                        @if ($kelas === 'income') <x-heroicon-o-arrow-trending-up /> @else <x-heroicon-o-arrow-trending-down /> @endif
                        <h2>{{ $judul }}</h2>
                    </header>
                    @if (count($laporan[$key]))
                        <div class="donut" style="background: {{ $buatGradien($laporan[$key]) }}"><span>{{ count($laporan[$key]) }}<small>kategori</small></span></div>
                        <div class="category-list">
                            @foreach ($laporan[$key] as $item)
                                <div><span><i style="background: {{ $item['warna'] }}"></i>{{ $item['nama'] }}</span><strong>{{ $formatRupiah($item['nominal']) }}</strong></div>
                            @endforeach
                            <div class="category-total"><span>Total</span><strong>{{ $formatRupiah($laporan[strtolower($judul)]) }}</strong></div>
                        </div>
                    @else
                        <div class="empty-state"><x-heroicon-o-chart-pie /><p>Belum ada {{ strtolower($judul) }} pada periode ini.</p></div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>

    <style>
        .laporan-page { display:grid; gap:1.25rem; color:var(--gray-700); }
        .laporan-filter { display:flex; align-items:end; justify-content:space-between; gap:1rem; padding:1rem; border:1px solid var(--gray-200); border-radius:1rem; background:white; box-shadow:0 1px 3px rgb(0 0 0 / .05); }
        .laporan-filter__book { display:grid; gap:.4rem; min-width:240px; } .laporan-filter label { font-size:.8rem; font-weight:600; color:var(--gray-500); }
        .laporan-filter select,.laporan-datebar input { border:1px solid var(--gray-300); border-radius:.6rem; background:white; padding:.55rem .75rem; }
        .laporan-periods { display:flex; padding:.25rem; border-radius:.75rem; background:var(--gray-100); }
        .laporan-periods button { padding:.55rem 1rem; border-radius:.55rem; font-size:.875rem; font-weight:600; transition:.2s; }
        .laporan-periods button.active { color:white; background:#d39b18; box-shadow:0 2px 6px rgb(163 112 0 / .2); }
        .laporan-datebar { display:flex; align-items:center; justify-content:center; gap:1rem; padding:.9rem 1rem; border-radius:1rem; color:white; background:linear-gradient(105deg,#3f4650,#686f78); }
        .laporan-datebar button { width:2.3rem; height:2.3rem; border-radius:999px; background:rgb(255 255 255 / .12); }
        .laporan-datebar strong { min-width:170px; text-align:center; text-transform:capitalize; } .laporan-datebar input { color:var(--gray-700); }
        .laporan-range { display:flex; align-items:center; gap:.75rem; } .laporan-range label { display:flex; align-items:center; gap:.45rem; font-size:.8rem; }
        .laporan-card { overflow:hidden; border:1px solid var(--gray-200); border-radius:1rem; background:white; box-shadow:0 2px 10px rgb(0 0 0 / .05); }
        .laporan-card header { display:flex; align-items:center; gap:.65rem; padding:1rem 1.25rem; border-bottom:1px solid var(--gray-200); background:linear-gradient(90deg,var(--gray-100),white); }
        .laporan-card header svg { width:1.4rem; } .laporan-card h2 { font-size:1.1rem; font-weight:700; }
        .laporan-summary__content { display:grid; grid-template-columns:1fr 1fr; gap:2rem; padding:1.5rem; }
        .laporan-totals { display:grid; align-content:start; } .laporan-totals>div { display:flex; justify-content:space-between; gap:1rem; padding:.8rem; border-bottom:1px dashed var(--gray-200); }
        .laporan-totals .income { margin-top:.75rem; color:#357858; background:#edf8f1; } .laporan-totals .expense { color:#a43e47; background:#fdf0f1; }
        .laporan-totals .accumulation { font-weight:700; background:var(--gray-50); } .laporan-totals .ending { margin-top:.75rem; font-weight:700; border-bottom:2px solid var(--gray-300); }
        .bar-chart { min-height:270px; display:grid; grid-template-rows:1fr auto; padding:1rem 1rem 0; }
        .bar-chart__plot { display:flex; align-items:end; justify-content:center; gap:1rem; border-bottom:1px solid var(--gray-300); background:repeating-linear-gradient(to bottom,transparent 0,transparent 24%,var(--gray-100) 25%); }
        .bar { position:relative; width:36%; min-height:2px; border-radius:.5rem .5rem 0 0; transition:height .3s; } .bar span { position:absolute; top:-1.6rem; width:100%; text-align:center; font-size:.72rem; font-weight:600; }
        .income-bar { color:#357858; background:linear-gradient(#78ba96,#bce0ca); } .expense-bar { color:#a43e47; background:linear-gradient(#d77f85,#efb8bb); }
        .bar-chart__labels { display:flex; justify-content:center; gap:1rem; } .bar-chart__labels span { width:36%; padding:.65rem 0; text-align:center; font-size:.75rem; }
        .laporan-categories { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; } .category-card { padding-bottom:1.25rem; }
        .category-card.income header svg { color:#438364; } .category-card.expense header svg { color:#b64c55; }
        .donut { position:relative; width:190px; height:190px; margin:1.75rem auto; border-radius:50%; }
        .donut::after { content:''; position:absolute; inset:28%; border-radius:50%; background:white; }
        .donut span { position:absolute; z-index:1; inset:0; display:grid; place-content:center; text-align:center; font-size:1.4rem; font-weight:700; } .donut small { display:block; color:var(--gray-400); font-size:.65rem; font-weight:500; }
        .category-list { margin:0 1rem; } .category-list>div { display:flex; justify-content:space-between; gap:1rem; padding:.65rem .5rem; border-bottom:1px dotted var(--gray-300); font-size:.85rem; }
        .category-list span { display:flex; align-items:center; gap:.5rem; } .category-list i { width:.65rem; height:.65rem; border-radius:50%; } .category-list .category-total { font-weight:700; border-bottom:2px solid var(--gray-400); background:var(--gray-50); }
        .empty-state { min-height:300px; display:grid; place-content:center; justify-items:center; gap:.75rem; color:var(--gray-400); } .empty-state svg { width:3rem; }
        .dark .laporan-page { color:var(--gray-200); } .dark .laporan-filter,.dark .laporan-card { border-color:var(--gray-700); background:var(--gray-900); }
        .dark .laporan-card header { border-color:var(--gray-700); background:linear-gradient(90deg,var(--gray-800),var(--gray-900)); } .dark .donut::after { background:var(--gray-900); }
        .dark .laporan-filter select,.dark .laporan-datebar input { border-color:var(--gray-600); color:var(--gray-100); background:var(--gray-800); }
        .dark .laporan-periods,.dark .laporan-totals .accumulation,.dark .category-list .category-total { background:var(--gray-800); }
        .dark .laporan-totals .income { background:rgb(53 120 88 / .18); } .dark .laporan-totals .expense { background:rgb(164 62 71 / .18); }
        @media (max-width:800px) { .laporan-filter { align-items:stretch; flex-direction:column; } .laporan-periods { overflow:auto; } .laporan-periods button { flex:1; } .laporan-summary__content,.laporan-categories { grid-template-columns:1fr; } .laporan-range { flex-wrap:wrap; justify-content:center; } .laporan-datebar { flex-wrap:wrap; } }
    </style>
</x-filament-panels::page>
