@vite('resources/css/filament-toolbar.css')

@php
            $bulanOptions = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
            $dompetOptions = $this->getDompetOptions();
            $bukuKasOptions = $this->getBukuKasOptions();
            $periodUrl = fn(string $month, string|int $year) => $listUrl . '?' . http_build_query(array_filter([
                'filter_month' => $month,
                'filter_year' => $year,
                'filter_buku_kas' => $filterBukuKas,
                'filter_dompet' => $filterDompet,
            ], fn ($value) => filled($value)));
            $filterUrl = fn(string $bukuKasId, ?string $dompetId = null) => $listUrl . '?' . http_build_query(
                array_filter([
                    'filter_month' => $filterMonth,
                    'filter_year' => $filterYear,
                    'filter_buku_kas' => $bukuKasId,
                    'filter_dompet' => $dompetId ?? $filterDompet,
                ])
            );

            $resetFilterUrl = $listUrl . '?' . http_build_query([
                'filter_month' => date('m'),
                'filter_year' => date('Y'),
            ]);
@endphp

    <x-filament::section collapsible collapsed compact icon="heroicon-o-funnel" data-testid="filter-transaksi-section">
        <x-slot name="heading">Filter transaksi</x-slot>

        <div class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Periode transaksi</div>

        {{-- Desktop (lg): 1 baris [Periode] [Sumber Dana] [Kas] [Reset]. Layar sempit: Periode penuh, Sumber Dana & Kas 2 kolom, Reset penuh. --}}
        <div class="grid grid-cols-2 items-center gap-2 lg:grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)_auto]">

            {{-- Periode --}}
            <div class="col-span-2 flex items-center gap-1.5 lg:col-span-1">
                {{-- Navigasi ke bulan sebelumnya --}}
                <a href="{{ $this->getPreviousPeriodUrl() }}" aria-label="Bulan sebelumnya"
                    class="fi-btn fi-btn-size-sm inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-400 bg-gray-100 text-gray-800 shadow-sm transition hover:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="h-4 w-4" />
                </a>

                <div class="flex h-9 min-w-0 flex-1 items-center overflow-visible rounded-lg border border-gray-400 bg-gray-100 shadow-sm dark:border-white/10 dark:bg-white/5 lg:flex-none">
                    <x-filament::icon icon="heroicon-m-calendar-days" class="ml-2.5 h-4 w-4 shrink-0 text-gray-700 dark:text-gray-400" />

                    <div x-data="{ open: false }" class="relative h-full w-36">
                        <button type="button" aria-label="Bulan" aria-haspopup="menu" :aria-expanded="open.toString()" @click="open = !open" @keydown.escape="open = false" class="flex h-full w-full items-center justify-between border-0 bg-transparent py-0 pl-2 pr-2 text-sm font-semibold text-gray-950 focus:outline-none dark:text-white">
                            <span class="truncate">{{ $bulanOptions[$filterMonth] ?? 'Bulan' }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="h-4 w-4 shrink-0 text-gray-600 dark:text-gray-300" />
                        </button>
                        <div x-cloak x-show="open" @click.outside="open = false" role="menu" aria-label="Pilih bulan" class="absolute left-0 top-full z-50 mt-1 max-h-60 w-44 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            @foreach($bulanOptions as $value => $label)
                                <a role="menuitem" aria-current="{{ $filterMonth === $value ? 'true' : 'false' }}" href="{{ $periodUrl($value, $filterYear) }}" class="block px-3 py-1.5 text-sm {{ $filterMonth === $value ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <span class="h-4 w-px shrink-0 bg-gray-400 dark:bg-white/20"></span>

                    <div x-data="{ open: false }" class="relative h-full w-24">
                        <button type="button" aria-label="Tahun" aria-haspopup="menu" :aria-expanded="open.toString()" @click="open = !open" @keydown.escape="open = false" class="flex h-full w-full items-center justify-between border-0 bg-transparent py-0 pl-2.5 pr-2 text-sm font-semibold text-gray-950 focus:outline-none dark:text-white">
                            <span>{{ $filterYear }}</span>
                            <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="h-4 w-4 shrink-0 text-gray-600 dark:text-gray-300" />
                        </button>
                        <div x-cloak x-show="open" @click.outside="open = false" role="menu" aria-label="Pilih tahun" class="absolute right-0 top-full z-50 mt-1 max-h-60 w-28 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            @for($year = (int) date('Y'); $year >= (int) date('Y') - 5; $year--)
                                <a role="menuitem" aria-current="{{ (string) $filterYear === (string) $year ? 'true' : 'false' }}" href="{{ $periodUrl($filterMonth, $year) }}" class="block px-3 py-1.5 text-sm {{ (string) $filterYear === (string) $year ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">{{ $year }}</a>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Navigasi ke bulan berikutnya --}}
                <a href="{{ $this->getNextPeriodUrl() }}" aria-label="Bulan berikutnya"
                    class="fi-btn fi-btn-size-sm inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-400 bg-gray-100 text-gray-800 shadow-sm transition hover:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4" />
                </a>
            </div>

            {{-- Sumber dana --}}
            <div class="flex h-9 min-w-0 items-center rounded-lg border border-gray-400 bg-gray-100 shadow-sm dark:border-white/10 dark:bg-white/5">
                <x-filament::icon icon="heroicon-m-wallet" class="ml-2.5 h-4 w-4 shrink-0 text-gray-700 dark:text-gray-400" />
                <div x-data="{ open: false }" class="relative h-full min-w-0 flex-1">
                    <button id="sumber-dana-filter" type="button" aria-label="Sumber Dana" aria-haspopup="menu" :aria-expanded="open.toString()" @click="open = !open" @keydown.escape="open = false" class="flex h-full w-full items-center justify-between border-0 bg-transparent py-0 pl-2 pr-2 text-left text-sm font-semibold text-gray-950 focus:outline-none dark:text-white">
                        <span class="truncate">{{ filled($filterDompet) ? ($dompetOptions[$filterDompet] ?? 'Sumber Dana') : 'Semua Sumber Dana' }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="ml-2 h-5 w-5 shrink-0 text-gray-600 dark:text-gray-300" />
                    </button>
                    <div x-cloak x-show="open" @click.outside="open = false" role="menu" aria-label="Pilih sumber dana" class="absolute left-0 top-full z-50 mt-1 max-h-60 min-w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <a role="menuitem" aria-current="{{ blank($filterDompet) ? 'true' : 'false' }}" href="{{ $filterUrl($filterBukuKas ?? '', '') }}" class="block whitespace-nowrap px-3 py-1.5 text-sm {{ blank($filterDompet) ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">Semua Sumber Dana</a>
                        @foreach($dompetOptions as $id => $nama)
                            <a role="menuitem" aria-current="{{ (string) $filterDompet === (string) $id ? 'true' : 'false' }}" href="{{ $filterUrl($filterBukuKas ?? '', (string) $id) }}" class="block whitespace-nowrap px-3 py-1.5 text-sm {{ (string) $filterDompet === (string) $id ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">{{ $nama }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Kas --}}
            <div class="flex h-9 min-w-0 items-center rounded-lg border border-gray-400 bg-gray-100 shadow-sm dark:border-white/10 dark:bg-white/5">
                <x-filament::icon icon="heroicon-m-book-open" class="ml-2.5 h-4 w-4 shrink-0 text-gray-700 dark:text-gray-400" />
                <div x-data="{ open: false }" class="relative h-full min-w-0 flex-1">
                    <button id="buku-kas-filter" type="button" aria-label="Kas" aria-haspopup="menu" :aria-expanded="open.toString()" @click="open = !open" @keydown.escape="open = false" class="flex h-full w-full items-center justify-between border-0 bg-transparent py-0 pl-2 pr-2 text-left text-sm font-semibold text-gray-950 focus:outline-none dark:text-white">
                        <span class="truncate">{{ filled($filterBukuKas) ? ($bukuKasOptions[$filterBukuKas] ?? 'Kas') : 'Semua Kas' }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="ml-2 h-5 w-5 shrink-0 text-gray-600 dark:text-gray-300" />
                    </button>
                    <div x-cloak x-show="open" @click.outside="open = false" role="menu" aria-label="Pilih kas" class="absolute right-0 top-full z-50 mt-1 max-h-60 min-w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800 lg:left-0 lg:right-auto">
                        <a role="menuitem" aria-current="{{ blank($filterBukuKas) ? 'true' : 'false' }}" href="{{ $filterUrl('') }}" class="block whitespace-nowrap px-3 py-1.5 text-sm {{ blank($filterBukuKas) ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">Semua Kas</a>
                        @foreach($bukuKasOptions as $id => $nama)
                            <a role="menuitem" aria-current="{{ (string) $filterBukuKas === (string) $id ? 'true' : 'false' }}" href="{{ $filterUrl((string) $id) }}" class="block whitespace-nowrap px-3 py-1.5 text-sm {{ (string) $filterBukuKas === (string) $id ? 'bg-teal-700 text-white dark:bg-teal-700' : 'text-gray-900 hover:bg-teal-700 hover:text-white dark:text-gray-100 dark:hover:bg-teal-600' }}">{{ $nama }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Reset --}}
            <a href="{{ $resetFilterUrl }}"
                class="col-span-2 inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-gray-400 bg-gray-100 px-3 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-200 hover:text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white lg:col-span-1">
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                Reset filter
            </a>
        </div>
    </x-filament::section>

    <ul aria-label="Keterangan jenis transaksi" data-testid="legenda-transaksi" class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs font-medium">
        <li class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
            <x-filament::icon icon="heroicon-o-arrow-up-on-square" class="h-4 w-4" />
            <span>Pengeluaran</span>
        </li>
        <li class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
            <x-filament::icon icon="heroicon-o-arrow-down-on-square" class="h-4 w-4" />
            <span>Pemasukan</span>
        </li>
        <li class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400">
            <x-filament::icon icon="heroicon-o-arrow-path-rounded-square" class="h-4 w-4" />
            <span>Transfer antar kas</span>
        </li>
        <li class="inline-flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
            <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
            <span>Transfer antar sumber dana</span>
        </li>
    </ul>