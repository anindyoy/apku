<x-filament-panels::page>
    @vite('resources/css/filament-toolbar.css')
    @if (auth()->user()->isAdmin())
        {{ $this->content }}
    @else
        @php
            $sections = $this->visibleTabs();
            $firstTab = $sections[0]['key'] ?? null;
            $icons = [
                'transaksi' => 'heroicon-o-arrows-right-left',
                'kas-dompet' => 'heroicon-o-wallet',
                'utang-piutang' => 'heroicon-o-arrows-right-left',
                'langganan' => 'heroicon-o-sparkles',
            ];
        @endphp
        <div
            wire:key="dashboard-tabs-{{ md5(json_encode($sections)) }}"
            x-data="{ activeTab: @js($firstTab) }"
            class="dashboard-tabs min-w-0 [--dash-muted:#64748b] [--dash-line:#e2e8f0] [--dash-bg:#f8fafc] [&:is(.dark_*)]:[--dash-muted:#94a3b8] [&:is(.dark_*)]:[--dash-line:#334155] [&:is(.dark_*)]:[--dash-bg:#1e293b]"
        >
            @if ($sections)
                <div class="mb-3 overflow-x-auto border-b border-slate-200 [&:is(.dark_*)]:border-slate-700">
                    <div
                        role="tablist"
                        aria-label="Bagian dashboard"
                        class="flex min-w-max text-center text-sm font-medium"
                        x-on:keydown="
                            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes($event.key)) {
                                $event.preventDefault();
                                const tabs = Array.from($el.querySelectorAll('[role=tab]'));
                                const index = tabs.indexOf($event.target);
                                const next = $event.key === 'Home' ? 0 : $event.key === 'End' ? tabs.length - 1 : (index + ($event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                                tabs[next].focus();
                                tabs[next].click();
                            }
                        "
                    >
                        @foreach ($sections as $section)
                            <button
                                type="button"
                                id="dashboard-tab-{{ $section['key'] }}"
                                role="tab"
                                aria-controls="dashboard-panel-{{ $section['key'] }}"
                                aria-selected="{{ $section['key'] === $firstTab ? 'true' : 'false' }}"
                                tabindex="{{ $section['key'] === $firstTab ? 0 : -1 }}"
                                x-bind:aria-selected="activeTab === '{{ $section['key'] }}'"
                                x-bind:tabindex="activeTab === '{{ $section['key'] }}' ? 0 : -1"
                                x-on:click="activeTab = '{{ $section['key'] }}'"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-t-lg border-b-2 border-transparent px-4 py-3 text-slate-500 hover:border-slate-300 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-teal-600 [&[aria-selected=true]]:border-teal-600 [&[aria-selected=true]]:text-teal-600 [&:is(.dark_*)]:text-slate-400 [&:is(.dark_*)]:hover:text-slate-200 [&:is(.dark_*)[aria-selected=true]]:border-teal-400 [&:is(.dark_*)[aria-selected=true]]:text-teal-400"
                            >
                                <x-filament::icon :icon="$icons[$section['key']]" class="size-4 shrink-0" aria-hidden="true" />
                                {{ $section['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            @forelse ($sections as $section)
                @php
                    $key = $section['key'];
                    $items = $section['sections'];
                @endphp
                <section
                    id="dashboard-panel-{{ $key }}"
                    wire:key="dashboard-{{ $key }}"
                    role="tabpanel"
                    aria-labelledby="dashboard-tab-{{ $key }}"
                    tabindex="0"
                    data-section="{{ $key }}"
                    x-show="activeTab === '{{ $key }}'"
                    @if ($key !== $firstTab) x-cloak @endif
                    @class([
                        'dashboard-card min-w-0 rounded-lg bg-white p-4 ring-1 ring-slate-200 [--accent:#f59e0b] [&:is(.dark_*)]:bg-gray-900 [&:is(.dark_*)]:ring-slate-700 [&_.fi-ta-header-cell]:!py-2 sm:[&_.fi-ta-text:not(.fi-inline)]:!py-2 sm:[&_.fi-ta-cell:has(.fi-ta-actions)]:!py-2',
                        'dashboard-full-width' => in_array('transaksi', $items, true),
                    ])
                >
                    <div @class(['grid gap-5', 'sm:grid-cols-2' => count($items) > 1])>
                        @foreach ($items as $itemKey)
                            @php
                                $data = $this->sectionData($itemKey);
                                $manageUrl = match ($itemKey) {
                        'kas' => \App\Filament\Resources\BukuKasResource::getUrl('index'),
                        'dompet' => \App\Filament\Resources\DompetResource::getUrl('index'),
                        'utang' => \App\Filament\Resources\UtangResource::getUrl('index'),
                        'piutang' => \App\Filament\Resources\PiutangResource::getUrl('index'),
                        'langganan' => \App\Filament\Resources\LanggananResource::getUrl('index'),
                        default => null,
                    };
                                $itemLabel = \App\Filament\Pages\Dashboard::SECTIONS[$itemKey];
                            @endphp
                            <div class="dashboard-feature min-w-0" data-dashboard-item="{{ $itemKey }}">
                                @if (count($items) > 1)
                                    <h2 class="mb-2 text-sm font-semibold text-slate-700 [&:is(.dark_*)]:text-slate-200">{{ $itemLabel }}</h2>
                                @endif
                                @if ($itemKey === 'transaksi')
                        <div class="dashboard-actions mb-2 flex flex-wrap justify-end gap-2">
                            {{ $this->tambahTransaksiAction }}
                            <x-filament::button tag="a" :href="\App\Filament\Resources\TransaksiResource::getUrl()" color="gray" icon="heroicon-o-arrow-top-right-on-square">Lihat lengkap</x-filament::button>
                        </div>
                        {{ $this->table }}
                    @elseif (in_array($itemKey, ['kas', 'dompet']))
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Total saldo {{ $itemKey }}</p>
                        <p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $data->sum('saldo'), 0, ',', '.') }}</p>
                        <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Tersebar di {{ $data->count() }} {{ $itemKey }}</p>
                        <dl class="dashboard-list mt-3">
                            @forelse ($data as $item)
                                <div class="dashboard-row flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[color:var(--dash-line)] py-2 last:border-b-0 last:pb-0 [&_dt]:min-w-0 [&_dt]:flex-[1_1_7rem] [&_dt]:text-sm [&_dt]:font-medium [&_dt]:[overflow-wrap:anywhere] [&_dd]:max-w-full [&_dd]:text-sm [&_dd]:font-[650] [&_dd]:tabular-nums [&_dd]:[overflow-wrap:anywhere] [&_.dashboard-caption]:text-[.7rem] [&_.dashboard-caption]:font-normal">
                                    <dt>{{ $itemKey === 'kas' ? $item->nama_buku : $item->nama_dompet }}</dt>
                                    <dd>Rp {{ number_format((float) $item->saldo, 0, ',', '.') }}</dd>
                                </div>
                            @empty
                                <p class="dashboard-empty rounded-lg bg-[var(--dash-bg)] p-3 text-center text-sm text-[color:var(--dash-muted)]">Belum ada {{ $itemKey }}.</p>
                            @endforelse
                        </dl>
                    @elseif (in_array($itemKey, ['utang', 'piutang']))
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Total sisa {{ $itemKey }}</p><p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</p>
                        <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">3 catatan dengan aktivitas terbaru</p>
                        <dl class="dashboard-list mt-3">
                            @forelse ($data['latest'] as $item)
                                <div class="dashboard-row flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[color:var(--dash-line)] py-2 last:border-b-0 last:pb-0 [&_dt]:min-w-0 [&_dt]:flex-[1_1_7rem] [&_dt]:text-sm [&_dt]:font-medium [&_dt]:[overflow-wrap:anywhere] [&_dd]:max-w-full [&_dd]:text-sm [&_dd]:font-[650] [&_dd]:tabular-nums [&_dd]:[overflow-wrap:anywhere] [&_.dashboard-caption]:text-[.7rem] [&_.dashboard-caption]:font-normal">
                                    <dt>
                                        {{ $item->kepada }}
                                        <div class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">{{ $item->last_activity_date ? \Illuminate\Support\Carbon::parse($item->last_activity_date)->translatedFormat('d M Y, H:i') : $item->created_at->translatedFormat('d M Y') }} · {{ $item->nominal <= 0 ? 'Selesai' : 'Belum selesai' }}</div>
                                    </dt>
                                    <dd>Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</dd>
                                </div>
                            @empty
                                <p class="dashboard-empty rounded-lg bg-[var(--dash-bg)] p-3 text-center text-sm text-[color:var(--dash-muted)]">Belum ada {{ $itemKey }}.</p>
                            @endforelse
                        </dl>
                    @elseif ($itemKey === 'langganan')
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Langganan Anda</p><p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere] dashboard-membership rounded-lg bg-[linear-gradient(135deg,color-mix(in_srgb,var(--accent)_12%,transparent),var(--dash-bg))] p-3">{{ $data['active'] ? 'Premium aktif' : 'Reguler' }}</p>
                        @if ($data['active'])
                            <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Aktif sampai {{ $data['expires'] }} · {{ $data['days'] === 0 ? 'Berakhir hari ini' : $data['days'].' hari tersisa' }}</p>
                        @elseif ($data['expires'])
                            <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Masa aktif premium berakhir pada {{ $data['expires'] }}.</p>
                        @else
                            <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Belum memiliki langganan premium aktif.</p>
                        @endif
                                @endif
                                @if ($manageUrl)
                                    <div class="dashboard-footer mt-3 border-t border-[color:var(--dash-line)] pt-3">
                                        <x-filament::button tag="a" :href="$manageUrl" color="gray" icon="heroicon-o-cog-6-tooth" :aria-label="'Kelola '.$itemLabel">Kelola</x-filament::button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <x-filament::section heading="Dashboard disembunyikan" compact class="dashboard-full-width col-span-full">
                    Aktifkan bagian yang ingin ditampilkan melalui tombol Atur dashboard.
                </x-filament::section>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
