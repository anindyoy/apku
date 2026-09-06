<x-filament-panels::page>
    @unless (auth()->user()->isAdmin())
        <x-filament::section heading="Pilih akun yang sesuai kebutuhan Anda">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                Mulai dengan akun Reguler untuk mencatat keuangan harian. Aktifkan Premium ketika Anda membutuhkan lebih banyak kas dan dompet untuk memisahkan keuangan pribadi, usaha, tabungan, atau kebutuhan lainnya.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">Benefit</th>
                            <th class="px-4 py-2">Reguler</th>
                            <th class="px-4 py-2">Premium</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr><td class="py-2 pr-4">Kas</td><td class="px-4 py-2">Utama + 1 tambahan</td><td class="px-4 py-2">Bebas menambah</td></tr>
                        <tr><td class="py-2 pr-4">Dompet</td><td class="px-4 py-2">Utama + 1 tambahan</td><td class="px-4 py-2">Bebas menambah</td></tr>
                        <tr><td class="py-2 pr-4">Kelola transaksi pada kas dan dompet tambahan</td><td class="px-4 py-2">Terbatas</td><td class="px-4 py-2">Tersedia selama Premium aktif</td></tr>
                        <tr><td class="py-2 pr-4">Laporan dan ekspor PDF/Excel</td><td class="px-4 py-2">Tersedia</td><td class="px-4 py-2">Tersedia</td></tr>
                        <tr><td class="py-2 pr-4">Utang dan piutang</td><td class="px-4 py-2">Tersedia</td><td class="px-4 py-2">Tersedia</td></tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endunless

    {{ $this->table }}
</x-filament-panels::page>
