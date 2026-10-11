@php
    // Grid paket langganan mengikuti prototipe HTML.
    $adaPaket = $paketHemat->isNotEmpty();
@endphp

<div
    x-data="{
        fokusTombolBeli(id) {
            if (! id) {
                return;
            }
            const tombol = this.$el.querySelector('[data-paket-beli=\'' + id + '\']');
            if (tombol) {
                tombol.focus();
            }
        }
    }"
    x-on:modal-paket-ditutup.window="fokusTombolBeli($event.detail.paketId)"
    data-pilih-paket
>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
        <div class="relative px-6 pt-7 pb-6">
            <h2 class="text-2xl md:text-3xl font-light tracking-tight text-gray-900 dark:text-white">Upgrade <span class="font-extrabold">Premium</span></h2>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Nikmati semua fitur APKu: kas tanpa batas, import, laporan, dan kolaborasi.</p>
            @if ($adaPaket && $hargaTermurah)
                <div class="inline-block mt-3 md:mt-0 md:absolute md:top-5 md:right-7 md:text-xl bg-teal-700 text-white font-bold px-4 py-2 rounded shadow-lg -rotate-2 md:rotate-6" data-harga-termurah>
                    mulai Rp{{ number_format(round($hargaTermurah), 0, ',', '.') }} / bulan
                </div>
            @endif
        </div>
        <div class="bg-teal-700 dark:bg-teal-900 text-white px-6 py-4">
            <h3 class="text-xl md:text-2xl font-light">Pilih paket</h3>
        </div>
        <div class="p-6">
            @if ($sudahPremium)
                <div class="p-4 mb-5 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-700 dark:text-green-400" role="alert">
                    Anda sudah menjadi member Premium. Setelah pembelian, masa aktif Anda diperpanjang dari sisa yang ada.
                </div>
            @endif
            @if ($adaPaket)
                <div class="grid grid-cols-1 min-[481px]:grid-cols-2 lg:grid-cols-4 gap-5" data-grid-paket>
                    @foreach ($paketHemat as $paket)
                        @include('livewire.pilih-paket-langganan.kartu', ['paket' => $paket])
                    @endforeach
                </div>
                <p class="mt-7 text-center text-sm text-gray-500 dark:text-gray-400">
                    Klik <b>Beli</b> pada paket pilihan Anda, lalu konfirmasi metode pembayaran.
                </p>
            @else
                <p class="text-center text-sm text-gray-500 dark:text-gray-400" role="status">Belum ada paket yang tersedia.</p>
            @endif
        </div>
    </div>

    @if ($modalTerbuka)
        @include('livewire.pilih-paket-langganan.modal')
    @endif
</div>
