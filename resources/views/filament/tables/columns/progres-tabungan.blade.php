@php
    $record = $getRecord();
    $progres = $record ? $record->progresTabungan() : null;
    $user = auth()->user();
    $isPremium = $user?->masaAktifBerlaku() ?? false;
@endphp

@if ($record && $record->is_tabungan && $progres)
    <div class="mt-2 space-y-1.5 border-t border-gray-200 dark:border-gray-700 pt-2 text-xs">
        @if ($record->goal)
            <div class="flex items-center justify-between text-gray-700 dark:text-gray-300">
                <span>Target: Rp {{ number_format((float) $record->goal, 0, ',', '.') }}</span>
                <span class="font-semibold">{{ $progres->persen }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 overflow-hidden">
                <div class="bg-primary-600 h-2 rounded-full dark:bg-primary-500 transition-all duration-300" style="width: {{ $progres->barPersen }}%"></div>
            </div>
        @endif

        @if ($record->tanggal_goal)
            <div class="flex items-center gap-1.5 pt-0.5">
                @if ($isPremium && $progres->status)
                    @php
                        $badgeClasses = match ($progres->status) {
                            'tercapai' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            'terlewat' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                            'mendekati' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClasses }}">
                        {{ $progres->labelStatus }}
                    </span>
                @else
                    <span class="text-gray-500 dark:text-gray-400">
                        Tenggat: {{ $progres->tanggalDeadlineFormatted }}
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
