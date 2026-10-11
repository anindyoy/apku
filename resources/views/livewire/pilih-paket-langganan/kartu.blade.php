@php
    // Kartu paket langganan mengikuti struktur prototipe.
    $adaHemat = ($paket['hemat_bulan'] ?? 0) > 0;
    $isTerbaik = (bool) ($paket['is_terbaik'] ?? false);
@endphp

<article
    class="relative flex flex-col rounded-xl bg-white p-5 dark:bg-gray-800 {{ $isTerbaik ? 'border-2 border-teal-600 shadow-lg ring-4 ring-teal-100 dark:ring-teal-900/40' : 'border border-gray-200 dark:border-gray-700' }}"
    data-paket-kartu="{{ $paket['id'] }}"
>
    @if ($isTerbaik)
        <span class="absolute -top-3 left-5 rounded-full bg-amber-300 px-3 py-0.5 text-xs font-bold text-teal-950">Paling hemat</span>
    @endif
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $paket['label'] }}</h3>
    <div class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Rp{{ number_format($paket['harga'], 0, ',', '.') }}</div>
    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Rp{{ number_format(round($paket['per_bulan']), 0, ',', '.') }} / bulan
        @if ($adaHemat)
            <s class="ml-1 text-gray-400">Rp{{ number_format(round($paket['harga_coret']), 0, ',', '.') }}</s>
        @endif
    </div>
    <div class="mt-4 mb-5 min-h-[2.25rem] text-sm {{ $adaHemat ? 'font-semibold text-teal-700 dark:text-teal-400' : 'text-gray-500 dark:text-gray-400' }}">
        @if ($adaHemat)
            Hemat Rp{{ number_format(round($paket['hemat_bulan']), 0, ',', '.') }} / bulan ({{ $paket['persen'] }}%)
        @else
            Harga acuan per bulan
        @endif
    </div>
    <button
        type="button"
        wire:click="pilihPaket({{ $paket['id'] }})"
        wire:loading.attr="disabled"
        data-paket-beli="{{ $paket['id'] }}"
        class="beli mt-auto w-full rounded-lg px-5 py-2.5 text-sm font-medium focus:outline-none focus:ring-4 {{ $isTerbaik ? 'bg-teal-700 text-white hover:bg-teal-800 focus:ring-teal-300 dark:bg-teal-600 dark:hover:bg-teal-700 dark:focus:ring-teal-800' : 'border border-teal-700 text-teal-700 hover:bg-teal-700 hover:text-white focus:ring-teal-300 dark:border-teal-500 dark:text-teal-500 dark:hover:bg-teal-500 dark:hover:text-white dark:focus:ring-teal-800' }}"
    >
        Beli paket
    </button>
</article>
