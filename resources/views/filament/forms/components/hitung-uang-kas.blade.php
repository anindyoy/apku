@php
    $daftarPecahan = collect($kelompokPecahan)
        ->flatMap(fn (array $pecahan, string $kelompok): array => collect($pecahan)
            ->map(fn (int $nominal, string $kunci): array => [
                'kelompok' => $kelompok,
                'kunci' => $kunci,
                'nominal' => $nominal,
            ])->all())
        ->values()
        ->all();
@endphp

<div
    class="space-y-3"
    x-id="['toggle-uang-logam']"
    x-data="{
        pecahan: $wire.entangle(@js($getStatePath())).live,
        denominasi: @js($daftarPecahan),
        uangLogamTerbuka: false,
        jumlah(kunci) {
            return Math.max(0, Number(this.pecahan?.[kunci]) || 0)
        },
        ubah(kunci, selisih) {
            this.pecahan = {
                ...(this.pecahan ?? {}),
                [kunci]: Math.max(0, this.jumlah(kunci) + selisih),
            }
        },
        subtotal(kunci, nominal) {
            return this.jumlah(kunci) * nominal
        },
        total() {
            return this.denominasi.reduce((total, pecahan) => total + (this.jumlah(pecahan.kunci) * pecahan.nominal), 0)
        },
        formatRupiah(nominal) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nominal)
        },
        ulangi() {
            this.pecahan = Object.fromEntries(
                this.denominasi.map(pecahan => [pecahan.kunci, null]),
            )
        },
    }"
>
    <div class="text-sm font-semibold text-slate-800 dark:text-gray-100">💰 Hitung Uang Kas</div>
    <p class="text-sm text-slate-600 dark:text-gray-400">Masukkan jumlah lembar atau keping tiap pecahan. Total dihitung otomatis.</p>

    @foreach ($kelompokPecahan as $kelompok => $daftarNominal)
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @if ($kelompok === 'Uang Logam')
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-gray-300" x-bind:for="$id('toggle-uang-logam')">
                        {{ $kelompok }}
                    </label>
                    <label class="inline-flex cursor-pointer items-center">
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            x-bind:id="$id('toggle-uang-logam')"
                            x-model="uangLogamTerbuka"
                            aria-label="Tampilkan pecahan uang logam"
                        >
                        <span class="flex h-6 w-11 items-center rounded-full bg-gray-300 p-0.5 transition-colors peer-checked:bg-blue-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:bg-gray-600 dark:peer-focus:ring-blue-800">
                            <span
                                class="h-5 w-5 rounded-full border border-gray-300 bg-white transition-transform dark:border-gray-600"
                                x-bind:style="`transform: translateX(${uangLogamTerbuka ? 20 : 0}px)`"
                            ></span>
                        </span>
                    </label>
                </div>
            @else
                <div class="border-b border-slate-200 bg-blue-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-blue-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ $kelompok }}
                </div>
            @endif
            <div
                @class([
                    'grid grid-cols-1 gap-2 p-2 sm:grid-cols-2 sm:p-3' => $kelompok === 'Uang Kertas',
                    'divide-y divide-slate-100 dark:divide-gray-800' => $kelompok === 'Uang Logam',
                ])
                @if ($kelompok === 'Uang Logam')
                    id="pecahan-uang-logam"
                    x-show="uangLogamTerbuka"
                    x-cloak
                @endif
            >
                @foreach ($daftarNominal as $kunci => $nominal)
                    <div @class([
                        'grid grid-cols-[minmax(0,1fr)_auto_minmax(4rem,auto)] items-center gap-2 rounded-lg px-2 py-2 sm:px-3' => $kelompok === 'Uang Kertas',
                        'grid grid-cols-[minmax(0,1fr)_auto_minmax(4rem,auto)] items-center gap-2 px-2 py-2 sm:px-3' => $kelompok === 'Uang Logam',
                        'border border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800' => $kelompok === 'Uang Kertas',
                    ])>
                        <div class="min-w-0 text-sm font-semibold text-slate-800 dark:text-gray-100">
                            Rp {{ number_format($nominal, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                aria-label="Kurangi satu pecahan Rp {{ number_format($nominal, 0, ',', '.') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg leading-none text-slate-700 hover:bg-blue-50 hover:text-blue-700 active:bg-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:active:bg-gray-700"
                                x-on:click="ubah(@js($kunci), -1)"
                            >−</button>
                            <input
                                type="number"
                                min="0"
                                max="999999"
                                step="1"
                                inputmode="numeric"
                                placeholder="0"
                                aria-label="Jumlah pecahan Rp {{ number_format($nominal, 0, ',', '.') }}"
                                class="h-9 w-12 rounded-lg border border-slate-300 bg-white px-1 text-center text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                x-model.number="pecahan[@js($kunci)]"
                            >
                            <button
                                type="button"
                                aria-label="Tambah satu pecahan Rp {{ number_format($nominal, 0, ',', '.') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg leading-none text-slate-700 hover:bg-blue-50 hover:text-blue-700 active:bg-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:active:bg-gray-700"
                                x-on:click="ubah(@js($kunci), 1)"
                            >+</button>
                        </div>
                        <div class="min-w-16 text-right text-xs text-slate-600 dark:text-gray-400" x-text="formatRupiah(subtotal(@js($kunci), {{ $nominal }}))"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900 dark:bg-blue-950">
        <span class="text-sm font-semibold text-blue-900 dark:text-gray-300">Total kas</span>
        <span class="text-lg font-bold text-blue-700 dark:text-blue-300" x-text="formatRupiah(total())"></span>
    </div>

    <button
        type="button"
        class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900"
        x-on:click="ulangi()"
    >Ulangi</button>
</div>
