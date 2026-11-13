<x-filament-panels::page>
    @vite('resources/css/filament-toolbar.css')
    @if (auth()->user()->isAdmin())
        {{ $this->content }}
    @else
        @php
            $trialBanner = $this->sectionData('langganan')['trial'] ?? null;
            $infoTrialDashboard = $this->infoTrialPremium();
        @endphp
        @if ($trialBanner)
            @include('filament.components.trial-premium-banner', ['trial' => $trialBanner])
        @else
            {{-- Banner ajakan di header agar akun layak langsung melihat CTA tanpa membuka kartu Langganan. --}}
            @include('filament.components.trial-premium-cta', ['info' => $infoTrialDashboard])
        @endif
        @php
            $sections = $this->visibleTabs();
            $icons = [
                'transaksi' => 'heroicon-o-arrows-right-left',
                'kas-dompet' => 'heroicon-o-wallet',
                'utang-piutang' => 'heroicon-o-arrows-right-left',
                'langganan' => 'heroicon-o-sparkles',
            ];
        @endphp
        <div
            wire:key="dashboard-cards-{{ md5(json_encode($sections)) }}"
            x-data="{
                open: (() => {
                    const wide = window.matchMedia('(min-width: 768px)').matches;
                    return {
                        @foreach ($sections as $section)
                            @js($section['key']): wide || @js($section['key']) === 'kas-dompet'@if (! $loop->last),@endif
                        @endforeach
                    };
                })(),
                get anyOpen() { return Object.values(this.open).some(Boolean) },
                toggleAll() {
                    const value = ! this.anyOpen;
                    for (const key in this.open) this.open[key] = value;
                }
            }"
            class="dashboard-cards min-w-0 [--dash-muted:#64748b] [--dash-line:#e2e8f0] [--dash-bg:#f8fafc] [&:is(.dark_*)]:[--dash-muted:#94a3b8] [&:is(.dark_*)]:[--dash-line:#334155] [&:is(.dark_*)]:[--dash-bg:#1e293b]"
        >
            @if ($sections)
                <div class="mb-3 flex justify-end">
                    <button
                        type="button"
                        x-on:click="toggleAll()"
                        class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-teal-600 hover:text-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 [&:is(.dark_*)]:text-teal-400 [&:is(.dark_*)]:hover:text-teal-300"
                    >
                        <x-filament::icon icon="heroicon-o-chevron-down" class="size-4 transition-transform" x-bind:class="anyOpen ? 'rotate-180' : ''" aria-hidden="true" />
                        <span x-text="anyOpen ? 'Ciutkan semua' : 'Buka semua'">Ciutkan semua</span>
                    </button>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-[18px]">
            @forelse ($sections as $section)
                @php
                    $key = $section['key'];
                    $items = $section['sections'];
                @endphp
                <section
                    id="dashboard-card-{{ $key }}"
                    wire:key="dashboard-{{ $key }}"
                    data-section="{{ $key }}"
                    @class([
                        'dashboard-card min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white [--accent:#f59e0b] [&:is(.dark_*)]:border-gray-800 [&:is(.dark_*)]:bg-gray-900 [&_.fi-ta-header-cell]:!py-2 sm:[&_.fi-ta-text:not(.fi-inline)]:!py-2 sm:[&_.fi-ta-cell:has(.fi-ta-actions)]:!py-2',
                        'lg:col-span-6' => in_array($key, ['kas-dompet', 'utang-piutang'], true),
                        'lg:col-span-8' => $key === 'transaksi',
                        'lg:col-span-4' => $key === 'langganan',
                    ])
                >
                    <h2 class="w-full text-base font-semibold">
                        <button
                            type="button"
                            id="dashboard-toggle-{{ $key }}"
                            aria-controls="dashboard-content-{{ $key }}"
                            aria-expanded="{{ $key === 'kas-dompet' ? 'true' : 'false' }}"
                            x-bind:aria-expanded="open['{{ $key }}']"
                            x-on:click="open['{{ $key }}'] = ! open['{{ $key }}']"
                            class="flex w-full items-center justify-between px-4 py-3.5 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 focus-visible:outline-offset-2 md:px-5 md:py-4"
                        >
                            <span class="flex items-center gap-2">
                                <x-filament::icon :icon="$icons[$key]" class="size-4 text-teal-600 [&:is(.dark_*)]:text-teal-400" aria-hidden="true" />
                                {{ $section['label'] }}
                            </span>
                            <span class="grid size-[30px] shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 [&:is(.dark_*)]:border-slate-700 [&:is(.dark_*)]:bg-slate-800/70 [&:is(.dark_*)]:text-slate-400">
                                <x-filament::icon icon="heroicon-o-chevron-down" class="size-4 transition-transform" x-bind:class="open['{{ $key }}'] ? 'rotate-180' : ''" aria-hidden="true" />
                            </span>
                        </button>
                    </h2>
                    @php $pemeriksaanSaldo = $key === 'kas-dompet' ? $this->pemeriksaanSaldo() : null; @endphp
                    <div x-show="!open['{{ $key }}']" x-cloak class="flex flex-wrap items-baseline gap-x-5 gap-y-1 px-4 pb-3.5 md:px-5">
                        @foreach ($items as $itemKey)
                            @php $summary = $this->sectionData($itemKey); @endphp
                            @if (in_array($itemKey, ['kas', 'dompet'], true))
                                @php
                                    $summaryTotal = $itemKey === 'kas' && ! auth()->user()->hitungTabunganDiTotal()
                                        ? $summary->where('is_tabungan', false)->sum('saldo')
                                        : $summary->sum('saldo');
                                    $summaryLabel = $itemKey === 'dompet' ? 'Sumber dana' : 'Kas';
                                @endphp
                                <span>
                                    <span class="mr-1.5 text-xs text-[color:var(--dash-muted)]">{{ $summaryLabel }}</span>
                                    <span class="font-bold tabular-nums">{{ 'Rp '.number_format((float) $summaryTotal, 0, ',', '.') }}</span>
                                </span>
                            @elseif (in_array($itemKey, ['utang', 'piutang'], true))
                                <span>
                                    <span class="mr-1.5 text-xs text-[color:var(--dash-muted)]">{{ ucfirst($itemKey) }}</span>
                                    <span class="font-bold tabular-nums">{{ 'Rp '.number_format((float) $summary['total'], 0, ',', '.') }}</span>
                                </span>
                            @elseif ($itemKey === 'langganan')
                                <span>
                                    <span class="mr-1.5 text-xs text-[color:var(--dash-muted)]">Paket</span>
                                    <span class="font-bold">{{ $summary['active'] ? 'Premium aktif' : 'Reguler' }}</span>
                                </span>
                            @else
                                <span class="text-xs text-[color:var(--dash-muted)]">{{ $this->getTableRecords()->count() }} transaksi terbaru</span>
                            @endif
                        @endforeach
                        @if ($pemeriksaanSaldo)
                            <span @class([
                                'inline-flex items-center gap-1.5 text-xs font-medium',
                                'text-emerald-500' => $pemeriksaanSaldo['seimbang'],
                                'text-amber-500' => ! $pemeriksaanSaldo['seimbang'],
                            ])>
                                <span @class([
                                    'size-2 rounded-full',
                                    'bg-emerald-500' => $pemeriksaanSaldo['seimbang'],
                                    'bg-amber-500' => ! $pemeriksaanSaldo['seimbang'],
                                ])></span>
                                {{ $pemeriksaanSaldo['seimbang'] ? 'Seimbang' : 'Tidak cocok' }}
                            </span>
                        @endif
                    </div>
                    <div id="dashboard-content-{{ $key }}" x-show="open['{{ $key }}']" x-cloak class="px-4 pb-4 md:px-5 md:pb-5">
                    @if ($pemeriksaanSaldo)
                        <div @class([
                            'mb-4 rounded-lg border p-3 text-sm',
                            'border-emerald-300 bg-emerald-50 text-emerald-900 [&:is(.dark_*)]:border-emerald-800 [&:is(.dark_*)]:bg-emerald-950/40 [&:is(.dark_*)]:text-emerald-200' => $pemeriksaanSaldo['seimbang'],
                            'border-amber-300 bg-amber-50 text-amber-950 [&:is(.dark_*)]:border-amber-800 [&:is(.dark_*)]:bg-amber-950/40 [&:is(.dark_*)]:text-amber-100' => ! $pemeriksaanSaldo['seimbang'],
                        ]) role="status" aria-live="polite">
                            <p class="font-semibold">{{ $pemeriksaanSaldo['seimbang'] ? 'Saldo kas dan sumber dana seimbang' : 'Total saldo kas dan sumber dana tidak cocok' }}</p>
                            <p class="mt-1">Total {{ $pemeriksaanSaldo['cakupan_kas'] }}: Rp {{ number_format($pemeriksaanSaldo['saldo_kas'], 0, ',', '.') }} · Total sumber dana: Rp {{ number_format($pemeriksaanSaldo['saldo_sumber_dana'], 0, ',', '.') }}.</p>
                            <p class="mt-1">{{ $pemeriksaanSaldo['seimbang'] ? 'Selisih Rp 0.' : 'Selisih '.($pemeriksaanSaldo['selisih'] > 0 ? 'lebih pada kas' : 'lebih pada sumber dana').': Rp '.number_format(abs($pemeriksaanSaldo['selisih']), 0, ',', '.').'.' }} Pemeriksaan total tidak memastikan setiap akun cocok; periksa tiap sumber dana melalui <a class="font-semibold underline underline-offset-2" href="{{ \App\Filament\Resources\AuditSaldoDompetResource::getUrl() }}">Cocokkan Saldo</a>.</p>
                        </div>
                    @endif
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
                        {{-- Kunci dompet dipertahankan; teks UI memakai sumber dana. --}}
                        @php
                            $user = auth()->user();
                            $hitungTabungan = $user ? $user->hitungTabunganDiTotal() : true;
                            $totalSaldo = ($itemKey === 'kas' && ! $hitungTabungan)
                                ? $data->where('is_tabungan', false)->sum('saldo')
                                : $data->sum('saldo');
                            $eyebrow = match (true) {
                                $itemKey === 'dompet' => 'Total saldo sumber dana',
                                $itemKey === 'kas' && ! $hitungTabungan => 'Total saldo kas biasa',
                                default => 'Total saldo kas',
                            };
                            $caption = match (true) {
                                $itemKey === 'dompet' => 'Tersebar di ' . $data->count() . ' sumber dana',
                                $itemKey === 'kas' && ! $hitungTabungan => 'Tersebar di ' . $data->count() . ' kas (saldo tabungan dipisahkan)',
                                default => 'Tersebar di ' . $data->count() . ' kas',
                            };
                        @endphp
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">{{ $eyebrow }}</p>
                        @if ($pemeriksaanSaldo && ! $pemeriksaanSaldo['seimbang'])
                            <p class="mb-1 text-xs font-semibold text-amber-800 [&:is(.dark_*)]:text-amber-200">Bagian dari total yang tidak cocok. <a class="underline underline-offset-2" href="{{ \App\Filament\Resources\AuditSaldoDompetResource::getUrl() }}">Cocokkan Saldo</a></p>
                        @endif
                        <p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $totalSaldo, 0, ',', '.') }}</p>
                        <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">{{ $caption }}</p>
                        <dl class="dashboard-list mt-3">
                            @forelse ($data as $item)
                                @php
                                    $filter = $itemKey === 'kas'
                                        ? ['filter_buku_kas' => $item->id]
                                        : ['filter_dompet' => $item->id];
                                    $filterUrl = \App\Filament\Resources\TransaksiResource::getUrl('index', $filter);
                                    $filterLabel = $itemKey === 'kas' ? 'Kas' : 'Sumber Dana';
                                    $itemName = $itemKey === 'kas' ? $item->nama_buku : $item->nama_dompet;
                                @endphp
                                <div class="dashboard-row flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[color:var(--dash-line)] py-2 last:border-b-0 last:pb-0 [&_dt]:min-w-0 [&_dt]:flex-[1_1_7rem] [&_dt]:text-sm [&_dt]:font-medium [&_dt]:[overflow-wrap:anywhere] [&_dd]:max-w-full [&_dd]:text-sm [&_dd]:font-[650] [&_dd]:tabular-nums [&_dd]:[overflow-wrap:anywhere] [&_.dashboard-caption]:text-[.7rem] [&_.dashboard-caption]:font-normal">
                                    <dt>
                                        <a
                                            href="{{ $filterUrl }}"
                                            data-dashboard-transaction-filter="{{ $itemKey }}-{{ $item->id }}"
                                            aria-label="Lihat transaksi {{ $filterLabel }} {{ $itemName }}"
                                            class="block rounded-sm text-teal-700 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 [&:is(.dark_*)]:text-teal-400"
                                        >
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span>{{ $itemName }}</span>
                                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="size-3.5 shrink-0" aria-hidden="true" />
                                                @if ($itemKey === 'kas' && $item->is_tabungan)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[0.65rem] font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">Tabungan</span>
                                                @endif
                                            </div>
                                            @if ($itemKey === 'kas' && $item->is_tabungan)
                                                @php
                                                    $progres = $item->progresTabungan();
                                                    $isPremium = auth()->user()?->masaAktifBerlaku() ?? false;
                                                @endphp
                                                @if ($item->goal)
                                                    <div class="dashboard-caption mt-1 text-[.7rem] text-[color:var(--dash-muted)]">
                                                        Target: Rp {{ number_format((float) $item->goal, 0, ',', '.') }} ({{ $progres->persen }}%)
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 mt-1 overflow-hidden">
                                                        <div class="bg-primary-600 h-1.5 rounded-full dark:bg-primary-500" style="width: {{ $progres->barPersen }}%"></div>
                                                    </div>
                                                @endif
                                                @if ($item->tanggal_goal)
                                                    <div class="mt-1">
                                                        @if ($isPremium && $progres->status)
                                                            @php
                                                                $badgeClass = match ($progres->status) {
                                                                    'tercapai' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                                    'terlewat' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                                    'mendekati' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                                                };
                                                            @endphp
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[0.65rem] font-medium {{ $badgeClass }}">
                                                                {{ $progres->labelStatus }}
                                                            </span>
                                                        @else
                                                            <span class="dashboard-caption text-[.7rem] text-[color:var(--dash-muted)]">
                                                                Tenggat: {{ $progres->tanggalDeadlineFormatted }}
                                                            </span>
                                                        @endif
                                                </div>
                                                @endif
                                            @endif
                                        </a>
                                    </dt>
                                    <dd>
                                        <a
                                            href="{{ $filterUrl }}"
                                            aria-label="Lihat transaksi {{ $filterLabel }} {{ $itemName }}"
                                            class="rounded-sm text-teal-700 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-600 [&:is(.dark_*)]:text-teal-400"
                                        >Rp {{ number_format((float) $item->saldo, 0, ',', '.') }}</a>
                                    </dd>
                                </div>
                            @empty
                                <p class="dashboard-empty rounded-lg bg-[var(--dash-bg)] p-3 text-center text-sm text-[color:var(--dash-muted)]">Belum ada {{ $itemKey === 'dompet' ? 'sumber dana' : $itemKey }}.</p>
                            @endforelse
                        </dl>
                    @elseif (in_array($itemKey, ['utang', 'piutang']))
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Total sisa {{ $itemKey }}</p><p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</p>
                        <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">3 catatan dengan pembaruan terbaru</p>
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
                        {{-- Ajakan trial hanya tampil di header agar tidak ganda; kartu cukup menampilkan sisa trial. --}}
                        @if ($data['trial'] ?? null)
                            <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Trial Premium: {{ ($data['trial']['sisa'] ?? 0) === 0 ? 'berakhir hari ini' : ($data['trial']['sisa'] ?? 0).' hari tersisa' }} sampai {{ $data['trial']['berakhir'] ?? '-' }}.</p>
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
                    </div>
                </section>
            @empty
                <x-filament::section heading="Dashboard disembunyikan" compact class="dashboard-full-width col-span-full">
                    Aktifkan bagian yang ingin ditampilkan melalui tombol Atur dashboard.
                </x-filament::section>
            @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
