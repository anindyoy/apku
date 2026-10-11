@php
    // Modal konfirmasi memakai markup gaya Flowbite dari prototipe.
    $pesanVoucherError = $voucherValid ? '' : $pesanVoucher;
@endphp

<div
    x-data="{
        tutup() {
            $wire.tutupModal();
        }
    }"
    x-init="$nextTick(() => { const el = $el.querySelector('[data-modal-metode]'); if (el) { el.focus(); } })"
    x-on:keydown.escape.window="tutup()"
    x-on:click.self="tutup()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-3"
    role="dialog"
    aria-modal="true"
    aria-labelledby="judul-modal-paket"
    data-modal-paket
>
    <div class="relative w-full max-w-md bg-white rounded-lg shadow dark:bg-gray-700">
        <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
            <h3 id="judul-modal-paket" class="text-lg font-semibold text-gray-900 dark:text-white">Konfirmasi order</h3>
            <button
                type="button"
                wire:click="tutupModal"
                wire:loading.attr="disabled"
                aria-label="Tutup"
                data-modal-tutup-awal
                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-xl w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
            >&times;</button>
        </div>
        <div class="p-4 space-y-4">
            @error('paketId')
                <p class="p-3 text-sm text-red-700 rounded-lg bg-red-50 dark:bg-gray-600 dark:text-red-300" role="alert">{{ $message }}</p>
            @enderror
            <div>
                <label for="modal-metode-{{ $this->getId() }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Metode pembayaran</label>
                <select
                    id="modal-metode-{{ $this->getId() }}"
                    wire:model.live="metodeId"
                    data-modal-metode
                    @if ($metode->isEmpty()) disabled @endif
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-teal-500 focus:border-teal-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                >
                    @forelse ($metode as $item)
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @empty
                        <option value="">Belum ada metode pembayaran aktif</option>
                    @endforelse
                </select>
                @if ($metode->isEmpty())
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300" role="status">Hubungi admin untuk mengaktifkan metode pembayaran.</p>
                @endif
                @error('metodeId')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="modal-kode-{{ $this->getId() }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode voucher (opsional)</label>
                <div class="flex gap-2">
                    <input
                        id="modal-kode-{{ $this->getId() }}"
                        type="text"
                        wire:model="kodeVoucher"
                        wire:loading.attr="disabled"
                        wire:target="terapkanVoucher,buatOrder"
                        autocomplete="off"
                        placeholder="Contoh: HEMAT10"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-teal-500 focus:border-teal-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                    >
                    <button
                        type="button"
                        wire:click="terapkanVoucher"
                        wire:loading.attr="disabled"
                        wire:target="terapkanVoucher,buatOrder"
                        class="shrink-0 text-teal-700 border border-teal-700 hover:bg-teal-700 hover:text-white focus:ring-4 focus:outline-none focus:ring-teal-300 font-medium rounded-lg text-sm px-4 dark:border-teal-500 dark:text-teal-500 dark:hover:text-white dark:hover:bg-teal-500 dark:focus:ring-teal-800"
                    >Terapkan</button>
                </div>
                @error('kodeVoucher')
                    <p class="mt-1 min-h-[1.25rem] text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @if (filled($pesanVoucher))
                    <p role="status" class="mt-1 min-h-[1.25rem] text-sm {{ $voucherValid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $pesanVoucher }}</p>
                @elseif ($errors->has('kodeVoucher'))
                @else
                    <p class="mt-1 min-h-[1.25rem] text-sm" role="status"></p>
                @endif
                @if (filled($pesanVoucherError) && ! $errors->has('kodeVoucher'))
                    <span class="sr-only">{{ $pesanVoucherError }}</span>
                @endif
            </div>
            <div class="p-3 text-sm border border-gray-200 rounded-lg dark:border-gray-600 text-gray-900 dark:text-white" data-ringkasan>
                @if ($ringkasan)
                    <div class="flex justify-between py-0.5"><span>Paket</span><span>{{ $ringkasan['paket']['label'] }}</span></div>
                    <div class="flex justify-between py-0.5"><span>Harga</span><span>Rp{{ number_format($ringkasan['paket']['harga'], 0, ',', '.') }}</span></div>
                    @if (($ringkasan['paket']['hemat_total'] ?? 0) > 0)
                        <div class="flex justify-between py-0.5 text-green-700 dark:text-green-400"><span>Hemat dibanding acuan</span><span>Rp{{ number_format(round($ringkasan['paket']['hemat_total']), 0, ',', '.') }}</span></div>
                    @endif
                    @if ($voucherValid && $ringkasan['persen'] > 0)
                        <div class="flex justify-between py-0.5 text-green-700 dark:text-green-400"><span>Voucher {{ strtoupper(trim($kodeVoucher)) }} ({{ $ringkasan['persen'] }}%)</span><span>-Rp{{ number_format($ringkasan['potongan'], 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between py-0.5 mt-1.5 pt-2 border-t border-gray-200 dark:border-gray-600 text-base font-bold"><span>Total bayar</span><span>Rp{{ number_format($ringkasan['total'], 0, ',', '.') }}</span></div>
                @else
                    <p role="status">Paket tidak tersedia.</p>
                @endif
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 p-4 border-t border-gray-200 rounded-b dark:border-gray-600">
            <button
                type="button"
                wire:click="tutupModal"
                wire:loading.attr="disabled"
                wire:target="terapkanVoucher,buatOrder"
                class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-teal-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
            >Batal</button>
            <button
                type="button"
                wire:click="buatOrder"
                wire:loading.attr="disabled"
                wire:target="buatOrder,terapkanVoucher"
                @if ($metode->isEmpty() || ! $ringkasan) disabled @endif
                class="text-white bg-teal-700 hover:bg-teal-800 focus:ring-4 focus:ring-teal-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-teal-600 dark:hover:bg-teal-700 dark:focus:ring-teal-800 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
            ><span wire:loading.remove wire:target="buatOrder">Buat order</span><span wire:loading wire:target="buatOrder">Membuat order...</span></button>
        </div>
    </div>
</div>
