@vite('resources/css/filament-toolbar.css')

<style>
    /* Warna opsi select pada mode terang */
    .period-filter-select option {
        color: #1f2937;
        background-color: #ffffff;
    }
    /* Warna opsi select pada mode gelap */
    .dark .period-filter-select option {
        color: #e5e7eb;
        background-color: #1f2937;
    }
    .dark .period-filter-select option:checked {
        color: #ffffff;
        background-color: #374151;
    }
</style>

<x-filament-panels::page>
        @php
            $filterUrl = fn(string $bukuKasId, ?string $dompetId = null) => request()->url() . '?' . http_build_query(
                array_filter([
                    'filter_month' => $filterMonth,
                    'filter_year' => $filterYear,
                    'filter_buku_kas' => $bukuKasId,
                    'filter_dompet' => $dompetId ?? $filterDompet,
                ])
            );

            $resetFilterUrl = request()->url() . '?' . http_build_query([
                'filter_month' => date('m'),
                'filter_year' => date('Y'),
            ]);
        @endphp

    <section aria-labelledby="filter-transaksi-title"
        class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="space-y-1.5">
                    <label id="filter-transaksi-title" class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Periode transaksi
                    </label>

                    <div class="flex items-center gap-2">
                        {{-- Navigasi ke bulan sebelumnya --}}
                        <a href="{{ $this->getPreviousPeriodUrl() }}" aria-label="Bulan sebelumnya"
                            class="fi-btn fi-btn-size-sm inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm ring-1 ring-inset ring-gray-950/10 transition hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10">
                            <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                        </a>

                        <div class="flex h-10 items-center overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-inset ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="ml-3 h-5 w-5 shrink-0 text-gray-400" />

                            <select aria-label="Bulan" wire:model.live="filterMonth"
                                class="period-filter-select h-full border-0 bg-transparent py-0 pl-2 pr-8 text-sm font-semibold text-gray-950 focus:ring-0 dark:text-white">
                                @foreach(['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $value => $label)
                                    <option value="{{ $value }}" {{ $filterMonth === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>

                            <span class="h-5 w-px bg-gray-200 dark:bg-white/10"></span>

                            <select aria-label="Tahun" wire:model.live="filterYear"
                                class="period-filter-select h-full border-0 bg-transparent py-0 pl-3 pr-8 text-sm font-semibold text-gray-950 focus:ring-0 dark:text-white">
                                @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                    <option value="{{ $year }}" {{ (string) $filterYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>

                        {{-- Navigasi ke bulan berikutnya --}}
                        <a href="{{ $this->getNextPeriodUrl() }}" aria-label="Bulan berikutnya"
                            class="fi-btn fi-btn-size-sm inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm ring-1 ring-inset ring-gray-950/10 transition hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10">
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="dompet-filter" class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Dompet
                    </label>

                    <div class="flex h-10 min-w-56 items-center rounded-lg bg-white shadow-sm ring-1 ring-inset ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                        <x-filament::icon icon="heroicon-m-wallet" class="ml-3 h-5 w-5 shrink-0 text-gray-400" />
                        <select id="dompet-filter"
                            onchange="window.location.href='{{ $filterUrl($filterBukuKas ?? '', '__VALUE__') }}'.replace('__VALUE__', this.value)"
                            class="period-filter-select h-full w-full border-0 bg-transparent py-0 pl-2 pr-8 text-sm font-semibold text-gray-950 focus:ring-0 dark:text-white">
                            <option value="" {{ blank($filterDompet) ? 'selected' : '' }}>Semua Dompet</option>
                            @foreach($this->getDompetOptions() as $id => $nama)
                                <option value="{{ $id }}" {{ (string) $filterDompet === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="buku-kas-filter" class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Kas
                    </label>

                    <div class="flex h-10 min-w-56 items-center rounded-lg bg-white shadow-sm ring-1 ring-inset ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                        <x-filament::icon icon="heroicon-m-book-open" class="ml-3 h-5 w-5 shrink-0 text-gray-400" />
                        <select id="buku-kas-filter"
                            onchange="window.location.href='{{ $filterUrl('__VALUE__') }}'.replace('__VALUE__', this.value)"
                            class="period-filter-select h-full w-full border-0 bg-transparent py-0 pl-2 pr-8 text-sm font-semibold text-gray-950 focus:ring-0 dark:text-white">
                            <option value="" {{ blank($filterBukuKas) ? 'selected' : '' }}>Semua Kas</option>
                            @foreach($this->getBukuKasOptions() as $id => $nama)
                                <option value="{{ $id }}" {{ (string) $filterBukuKas === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <a href="{{ $resetFilterUrl }}"
                class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-lg bg-white px-3 text-sm font-semibold text-gray-600 shadow-sm ring-1 ring-inset ring-gray-950/10 transition hover:bg-gray-50 hover:text-gray-950 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white lg:self-auto">
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                Reset filter
            </a>
        </div>
    </section>

    {{ $this->content }}
</x-filament-panels::page>
