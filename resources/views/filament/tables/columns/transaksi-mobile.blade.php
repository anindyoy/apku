@php
    $record = $getRecord();
    $warna = \App\Filament\Resources\TransaksiResource::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer);
    $kategori = \App\Filament\Resources\TransaksiResource::getKategoriLabel($record);

    $saldoKasAkhir = $record->saldo;
    $saldoSumberDanaAkhir = $record->saldo_dompet;
@endphp

<div
    class="transaction-mobile-summary px-3 py-2.5"
    data-transaksi-mobile
    data-tone="{{ $warna }}">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <div class="text-sm font-semibold leading-5 text-gray-900 dark:text-white">
                {{ $record->jenis }}
            </div>

            <div class="flex flex-wrap items-center gap-x-1.5 text-[11px] leading-4 text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-600 dark:text-gray-300">
                    {{ $kategori ?: 'Tanpa kategori' }}
                </span>

                <span class="text-gray-300 dark:text-gray-600">•</span>

                <span>
                    {{ date('d M Y, H:i', strtotime($record->tanggal)) }}
                </span>
            </div>
        </div>

        <div class="shrink-0 text-right">
            <div class="text-[15px] font-bold leading-5 text-gray-900 dark:text-white">
                Rp {{ number_format((float) $record->nominal, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Deskripsi --}}
    @if ($record->deskripsi)
        <div class="mt-1">
            <div class="text-xs font-medium leading-3 tracking-wide text-gray-400 dark:text-gray-500">
                Deskripsi:
            </div>

            <div class="text-xs leading-4 text-gray-700 dark:text-gray-300">
                {{ $record->deskripsi }}
            </div>
        </div>
    @endif

    {{-- Sumber transaksi --}}
    <div class="mt-1 space-y-0.5 text-[11px] leading-4">
        @if ($record->tipe_transfer !== 'dompet')
            <div class="flex items-baseline gap-1.5">
                <span class="shrink-0 whitespace-nowrap text-gray-400 dark:text-gray-500">
                    Kas:
                </span>

                <span class="min-w-0 truncate font-medium text-gray-700 dark:text-gray-300">
                    {{ $record->buku_kas?->nama_buku ?? '-' }}
                </span>
            </div>
        @endif

        <div class="flex items-baseline gap-1.5">
            <span class="shrink-0 whitespace-nowrap text-gray-400 dark:text-gray-500">
                Sumber Dana:
            </span>

            <span class="min-w-0 truncate font-medium text-gray-700 dark:text-gray-300">
                {{ $record->sumberDana?->nama_dompet ?? '-' }}
            </span>
        </div>
    </div>

    {{-- Saldo --}}
    @if ($filterKasAktif && $record->tipe_transfer !== 'dompet')
        <div class="mt-1.5 flex items-center justify-between gap-2 border-t border-gray-200/70 pt-1.5 dark:border-gray-700">
            <span class="text-[10px] text-gray-500 dark:text-gray-400">
                Saldo Kas
            </span>

            <span class="text-[11px] font-semibold text-gray-800 dark:text-gray-200">
                Rp {{ number_format((float) $saldoKasAkhir, 0, ',', '.') }}
            </span>
        </div>
    @elseif ($filterSumberDanaAktif)
        <div class="mt-1.5 flex items-center justify-between gap-2 border-t border-gray-200/70 pt-1.5 dark:border-gray-700">
            <span class="text-[10px] text-gray-500 dark:text-gray-400">
                Saldo Sumber Dana
            </span>

            <span class="text-[11px] font-semibold text-gray-800 dark:text-gray-200">
                Rp {{ number_format((float) $saldoSumberDanaAkhir, 0, ',', '.') }}
            </span>
        </div>
    @endif
</div>
