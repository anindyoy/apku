<x-filament-panels::page>
    <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 dark:text-gray-400" x-data="{
        kas_aktif: $wire.entangle('id_kas_aktif'),
    }">
        @foreach ($list_kas as $item)
            <li class="me-2">
                <a href="#" class="inline-block px-4 py-1 text-white bg-blue-600 rounded-lg active"
                    :class="kas_aktif == {{ $item->id }} ? 'bg-blue-600' : 'bg-gray-500'" {{-- @click="$wire.kas_aktif = {{ $item->id }}" --}}
                    {{-- x-on:click="$wire.kas_aktif = {{ $item->id }}" aria-current="page" --}} wire:click="editKas({{ $item->id }})" aria-current="page">
                    {{ $item->nama_buku }}
                </a>
            </li>
        @endforeach
    </ul>

    <div>
        Saldo kas: Rp {{ $saldo }} |
        Saldo semua kas: Rp {{ $total_saldo }}
    </div>

    <div class="flex items-center my-1 gap-1" x-data="{
        selectedMonth: $wire.entangle('bulan'),
        selectedYear: $wire.entangle('tahun'),

        previousMonth() {
            if (this.selectedMonth == 1) {
                this.selectedMonth = 12;
                this.selectedYear--;
            } else {
                this.selectedMonth--;
            }
        },
        nextMonth() {
            if (this.selectedMonth == 12) {
                this.selectedMonth = 1;
                this.selectedYear++;
            } else {
                this.selectedMonth++;
            }
        }
    }">
        <button class="px-2 py-1 text-white bg-gray-500 rounded-lg" x-on:click="previousMonth()">←</button>
        <select id="countries" x-model="selectedMonth"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <select
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            x-model="selectedYear">
            @for ($year = $tahun_awal; $year <= date('Y'); $year++)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
        <button class="px-2 py-1 text-white bg-gray-500 rounded-lg" x-on:click="nextMonth()">→</button>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
