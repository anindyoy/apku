@vite('resources/css/filament-toolbar.css')

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

    <x-filament::section collapsible collapsed icon="heroicon-o-funnel" class="-mb-2" data-testid="filter-transaksi-section">
        <x-slot name="heading">Filter transaksi</x-slot>
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Periode transaksi
                    </label>

                    <div class="flex items-center gap-2">
                        {{-- Navigasi ke bulan sebelumnya --}}
                        <a href="{{ $this->getPreviousPeriodUrl() }}" aria-label="Bulan sebelumnya"
                            class="fi-btn fi-btn-size-sm inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5" />
                        </a>

                        <div class="flex h-10 items-center overflow-hidden rounded-lg border border-gray-300 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="ml-3 h-5 w-5 shrink-0 text-gray-500 dark:text-gray-400" />

                            <div class="relative h-full w-36">
                                <select aria-label="Bulan" wire:model.live="filterMonth"
                                    class="h-full w-full appearance-none border-0 bg-transparent bg-none py-0 pl-2 pr-10 text-sm font-semibold !text-gray-950 focus:ring-0 dark:!text-white">
                                    @foreach(['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $value => $label)
                                        <option class="bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200" value="{{ $value }}" {{ $filterMonth === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="pointer-events-none absolute inset-y-0 right-3.5 my-auto h-5 w-5 text-gray-600 dark:text-gray-300" />
                            </div>

                            <span class="h-5 w-px bg-gray-300 dark:bg-white/20"></span>

                            <div class="relative h-full w-24">
                                <select aria-label="Tahun" wire:model.live="filterYear"
                                    class="h-full w-full appearance-none border-0 bg-transparent bg-none py-0 pl-3 pr-10 text-sm font-semibold !text-gray-950 focus:ring-0 dark:!text-white">
                                    @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                        <option class="bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200" value="{{ $year }}" {{ (string) $filterYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                                <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="pointer-events-none absolute inset-y-0 right-3.5 my-auto h-5 w-5 text-gray-600 dark:text-gray-300" />
                            </div>
                        </div>

                        {{-- Navigasi ke bulan berikutnya --}}
                        <a href="{{ $this->getNextPeriodUrl() }}" aria-label="Bulan berikutnya"
                            class="fi-btn fi-btn-size-sm inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="dompet-filter" class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Dompet
                    </label>

                    <div class="flex h-10 min-w-56 items-center rounded-lg border border-gray-300 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-m-wallet" class="ml-3 h-5 w-5 shrink-0 text-gray-400" />
                        <div class="relative h-full min-w-0 flex-1">
                            <select id="dompet-filter"
                                onchange="window.location.href='{{ $filterUrl($filterBukuKas ?? '', '__VALUE__') }}'.replace('__VALUE__', this.value)"
                                class="h-full w-full appearance-none border-0 bg-transparent bg-none py-0 pl-2 pr-10 text-sm font-semibold !text-gray-950 focus:ring-0 dark:!text-white">
                                <option value="" {{ blank($filterDompet) ? 'selected' : '' }}>Semua Dompet</option>
                                @foreach($this->getDompetOptions() as $id => $nama)
                                    <option class="bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200" value="{{ $id }}" {{ (string) $filterDompet === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="pointer-events-none absolute inset-y-0 right-3.5 my-auto h-5 w-5 text-gray-600 dark:text-gray-300" />
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="buku-kas-filter" class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                        Kas
                    </label>

                    <div class="flex h-10 min-w-56 items-center rounded-lg border border-gray-300 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-m-book-open" class="ml-3 h-5 w-5 shrink-0 text-gray-400" />
                        <div class="relative h-full min-w-0 flex-1">
                            <select id="buku-kas-filter"
                                onchange="window.location.href='{{ $filterUrl('__VALUE__') }}'.replace('__VALUE__', this.value)"
                                class="h-full w-full appearance-none border-0 bg-transparent bg-none py-0 pl-2 pr-10 text-sm font-semibold !text-gray-950 focus:ring-0 dark:!text-white">
                                <option value="" {{ blank($filterBukuKas) ? 'selected' : '' }}>Semua Kas</option>
                                @foreach($this->getBukuKasOptions() as $id => $nama)
                                    <option class="bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-200" value="{{ $id }}" {{ (string) $filterBukuKas === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            <x-filament::icon icon="heroicon-o-chevron-down" data-testid="transaction-select-chevron" class="pointer-events-none absolute inset-y-0 right-3.5 my-auto h-5 w-5 text-gray-600 dark:text-gray-300" />
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ $resetFilterUrl }}"
                class="ml-auto inline-flex h-10 items-center justify-center gap-2 self-start rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-100 hover:text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white">
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                Reset filter
            </a>
        </div>
    </x-filament::section>
