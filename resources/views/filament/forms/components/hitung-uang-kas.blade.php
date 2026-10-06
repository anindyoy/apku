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
    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">💰 Hitung Uang Kas</div>
    <p class="text-sm text-gray-500 dark:text-gray-400">Masukkan jumlah lembar atau keping tiap pecahan. Total dihitung otomatis.</p>

    @foreach ($kelompokPecahan as $kelompok => $daftarNominal)
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            @if ($kelompok === 'Uang Logam')
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" x-bind:for="$id('toggle-uang-logam')">
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
                <div id="pecahan-uang-logam" x-show="uangLogamTerbuka" x-cloak>
            @else
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    {{ $kelompok }}
                </div>
            @endif
                @foreach ($daftarNominal as $kunci => $nominal)
                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(4rem,auto)] items-center gap-2 border-b border-gray-100 px-3 py-2 last:border-b-0 sm:px-4 dark:border-gray-800">
                        <div class="min-w-0 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($nominal, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                aria-label="Kurangi satu pecahan Rp {{ number_format($nominal, 0, ',', '.') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-lg leading-none text-gray-700 active:bg-blue-50 active:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:active:bg-gray-700"
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
                                class="h-9 w-12 rounded-lg border border-gray-300 bg-white px-1 text-center text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                x-model.number="pecahan[@js($kunci)]"
                            >
                            <button
                                type="button"
                                aria-label="Tambah satu pecahan Rp {{ number_format($nominal, 0, ',', '.') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-lg leading-none text-gray-700 active:bg-blue-50 active:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:active:bg-gray-700"
                                x-on:click="ubah(@js($kunci), 1)"
                            >+</button>
                        </div>
                        <div class="min-w-16 text-right text-xs text-gray-500 dark:text-gray-400" x-text="formatRupiah(subtotal(@js($kunci), {{ $nominal }}))"></div>
                    </div>
                @endforeach
            @if ($kelompok === 'Uang Logam')
                </div>
            @endif
        </div>
    @endforeach

    <div class="flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900 dark:bg-blue-950">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total kas</span>
        <span class="text-lg font-bold text-blue-700 dark:text-blue-300" x-text="formatRupiah(total())"></span>
    </div>

    <button
        type="button"
        class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900"
        x-on:click="ulangi()"
    >Ulangi</button>
</div>
