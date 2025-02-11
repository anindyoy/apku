<x-filament-panels::page>
    <div class="flex gap-4">
        <div class="flex-1">
            <h3 class="flex font-bold text-xl mb-2 text-green-500">
                <x-heroicon-o-arrow-down-on-square style="height: 25px" class="mr-1" />
                Pemasukan
            </h3>
            @livewire('kategori.pemasukan')
        </div>

        <div class="flex-1 text-xl mb-2 text-red-500">
            <h3 class="flex font-bold text-xl mb-2 text-red-500">
                <x-heroicon-o-arrow-up-on-square style="height: 25px" class="mr-1 " />
                Pengeluaran
            </h3>
            @livewire('kategori.pengeluaran')
        </div>
    </div>
</x-filament-panels::page>
