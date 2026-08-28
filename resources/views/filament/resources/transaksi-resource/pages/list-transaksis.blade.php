<style>
    /* Light mode select option colors */
    .period-filter-select option {
        color: #1f2937;
        background-color: #ffffff;
    }
    /* Dark mode select option colors */
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
    <div class="flex items-center gap-2 mb-4">
        {{-- Previous Month --}}
        <a href="{{ $this->getPreviousPeriodUrl() }}"
            class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition-colors duration-75 hover:bg-gray-50 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10 fi-color-primary">
            &lsaquo;
        </a>

        {{-- Month Select --}}
        <select wire:model.live="filterMonth"
            class="period-filter-select fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition-colors duration-75 hover:bg-gray-50 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10 fi-color-primary">
            @foreach(['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $value => $label)
                <option value="{{ $value }}" {{ $filterMonth === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        {{-- Year Select --}}
        <select wire:model.live="filterYear"
            class="period-filter-select fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition-colors duration-75 hover:bg-gray-50 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10 fi-color-primary">
            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                <option value="{{ $year }}" {{ (string) $filterYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
            @endfor
        </select>

        {{-- Next Month --}}
        <a href="{{ $this->getNextPeriodUrl() }}"
            class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition-colors duration-75 hover:bg-gray-50 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10 fi-color-primary">
            &rsaquo;
        </a>

        {{-- Buku Kas Filter --}}
        <div class="ml-4 flex items-center gap-2">
        @php
            $filterUrl = fn(string $bukuKasId = '') => request()->url() . '?' . http_build_query(array_merge(
                array_filter([
                    'filter_month' => $filterMonth,
                    'filter_year' => $filterYear,
                ]),
                $bukuKasId !== '' ? ['filter_buku_kas' => $bukuKasId] : []
            ));
        @endphp
        <label for="buku-kas-filter" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Buku Kas</label>
        <select
            id="buku-kas-filter"
            onchange="window.location.href='{{ $filterUrl('__VALUE__') }}'.replace('__VALUE__', this.value)"
            class="period-filter-select fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset transition-colors duration-75 hover:bg-gray-50 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10 fi-color-primary">
            <option value="">Semua Buku Kas</option>
            @foreach($this->getBukuKasOptions() as $id => $nama)
                <option value="{{ $id }}" {{ (string) $filterBukuKas === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
            @endforeach
        </select>
        </div>
    </div>

    {{ $this->content }}
</x-filament-panels::page>
