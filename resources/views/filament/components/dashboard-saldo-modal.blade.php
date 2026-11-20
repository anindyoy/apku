@props([
    'pemeriksaanSaldo',
    'key' => 'kas',
])

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800 shadow-sm [&:is(.dark_*)]:border-yellow-900 [&:is(.dark_*)]:bg-yellow-950/30 [&:is(.dark_*)]:text-yellow-100"
    role="status" aria-live="polite"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="flex items-center gap-2 font-medium leading-relaxed">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-4 flex-shrink-0 text-yellow-600 [&:is(.dark_*)]:text-yellow-400" />
            <span>Total saldo kas dan sumber dana tidak cocok.</span>
        </p>
        <button
            type="button"
            @click="open = true"
            class="inline-flex items-center gap-1 rounded-lg border border-yellow-300 bg-white px-2.5 py-1 text-xs font-medium text-yellow-700 shadow-sm transition hover:bg-yellow-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500 [&:is(.dark_*)]:border-yellow-800 [&:is(.dark_*)]:bg-yellow-900/40 [&:is(.dark_*)]:text-yellow-200 [&:is(.dark_*)]:hover:bg-yellow-900/60"
            aria-haspopup="dialog"
            :aria-expanded="open"
        >
            Selengkapnya
        </button>
    </div>

    {{-- Modal gaya Flowbite --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 dark:bg-gray-900/70"
        role="dialog"
        aria-modal="true"
        aria-labelledby="saldo-modal-title-{{ $key }}"
    >
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.away="open = false"
            class="relative w-full max-w-lg rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 id="saldo-modal-title-{{ $key }}" class="text-base font-semibold text-gray-900 dark:text-white">
                    Detail Pemeriksaan Saldo
                </h3>
                <button type="button" @click="open = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" aria-label="Tutup">
                    <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
                </button>
            </div>
            <div class="space-y-3 p-4 text-sm text-gray-600 dark:text-gray-300">
                <p>Total {{ $pemeriksaanSaldo['cakupan_kas'] }}: <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($pemeriksaanSaldo['saldo_kas'], 0, ',', '.') }}</span></p>
                <p>Total sumber dana: <span class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($pemeriksaanSaldo['saldo_sumber_dana'], 0, ',', '.') }}</span></p>
                <p>{{ $pemeriksaanSaldo['seimbang'] ? 'Selisih Rp 0.' : 'Selisih '.($pemeriksaanSaldo['selisih'] > 0 ? 'lebih pada kas' : 'lebih pada sumber dana').': Rp '.number_format(abs($pemeriksaanSaldo['selisih']), 0, ',', '.').'.' }}</p>
                <p>Pemeriksaan total tidak memastikan setiap akun cocok. Periksa tiap sumber dana melalui halaman <a class="font-semibold underline underline-offset-2" href="{{ \App\Filament\Resources\AuditSaldoDompetResource::getUrl() }}">Cocokkan Saldo</a>.</p>
            </div>
            <div class="flex items-center justify-end rounded-b border-t border-gray-200 p-4 dark:border-gray-700">
                <x-filament::button type="button" @click="open = false" color="gray">Tutup</x-filament::button>
            </div>
        </div>
    </div>
</div>
