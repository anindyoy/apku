<x-filament-panels::page>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Selamat datang di APKu</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Tinggal selangkah lagi. Siapkan kas dan kategori transaksi agar pencatatan pertama Anda lebih mudah.</p>
    </div>

    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" size="md" wire:loading.attr="disabled" wire:target="submit">
                Mulai menggunakan APKu
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
