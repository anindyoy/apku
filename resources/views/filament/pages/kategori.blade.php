<x-filament-panels::page>
    @vite('resources/css/filament-toolbar.css')
    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        Kategori mengelompokkan transaksi pada laporan dan bersifat opsional. Setiap kategori hanya dapat dipilih pada kas yang terhubung dengannya; satu kategori dapat dipakai di beberapa kas milik pemilik yang sama.
    </div>

    {{ $this->table }}
</x-filament-panels::page>
