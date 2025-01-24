<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-2">
        Buku kas anda:
        <ul class="flex flex-wrap text-center text-gray-500 dark:text-gray-400" x-data="{
            kas_aktif: $wire.entangle('id_kas_aktif'),

            editKas(id) {
                this.kas_aktif = id;
                this.$wire.editKas(id);
            }
        }">
            @foreach ($list_kas as $item)
                <li class="me-2">
                    <a href="#" class="inline-block px-4 py-1 text-white bg-blue-600 rounded-lg active"
                        :class="kas_aktif == {{ $item->id }} ? 'bg-blue-600' : 'bg-gray-500'"
                        @click="editKas({{ $item->id }})">
                        {{ $item->nama_buku }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="flex justify-between">
            <div class="y-auto">
                Saldo kas: Rp {{ $saldo }} |
                Saldo semua kas: Rp {{ $total_saldo }}
            </div>

            <span class="flex items-center my-1 gap-2" x-data="{
                selectedMonth: $wire.entangle('bulan'),
                selectedYear: $wire.entangle('tahun'),
                currentMonth: {{ date('n') }},
                currentYear: {{ date('Y') }},

                previousMonth() {
                    if (this.selectedMonth == 1) {
                        this.selectedMonth = 12;
                        this.selectedYear--;
                    } else {
                        this.selectedMonth--;
                    }
                    $wire.call('refreshTable');
                },

                nextMonth() {
                    if (this.selectedMonth == 12) {
                        this.selectedMonth = 1;
                        this.selectedYear++;
                    } else {
                        this.selectedMonth++;
                    }
                    $wire.call('refreshTable');
                }
            }">
                <button class="px-2 py-1 text-white bg-gray-500 rounded-lg" x-on:click="previousMonth()">←</button>

                <select x-model="selectedMonth"
                    class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                    class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    x-model="selectedYear">
                    @for ($year = $tahun_awal; $year <= date('Y'); $year++)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>

                <button class="px-2 py-1 text-white bg-gray-500 rounded-lg" x-on:click="nextMonth()"
                    x-show="selectedMonth != currentMonth && selectedYear != currentYear">→</button>

            </span>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
