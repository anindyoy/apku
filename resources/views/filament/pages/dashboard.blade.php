<x-filament-panels::page>
    @vite('resources/css/filament-toolbar.css')
    @if (auth()->user()->isAdmin())
        {{ $this->content }}
    @else
        <div class="dashboard-user-grid grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-3 [&>*]:min-w-0 [--dash-muted:#64748b] [--dash-line:#e2e8f0] [--dash-bg:#f8fafc] [&:is(.dark_*)]:[--dash-muted:#94a3b8] [&:is(.dark_*)]:[--dash-line:#334155] [&:is(.dark_*)]:[--dash-bg:#1e293b]">
            @forelse (array_filter($this->sections(), fn ($section) => $section['visible']) as $section)
                @php
                    $key = $section['key'];
                    $data = $this->sectionData($key);
                    $icon = match ($key) {
                        'kas' => 'heroicon-o-banknotes',
                        'dompet' => 'heroicon-o-wallet',
                        'utang' => 'heroicon-o-arrow-up-right',
                        'piutang' => 'heroicon-o-arrow-down-left',
                        'langganan' => 'heroicon-o-sparkles',
                        default => 'heroicon-o-arrows-right-left',
                    };
                    $manageUrl = match ($key) {
                        'kas' => \App\Filament\Resources\BukuKasResource::getUrl('index'),
                        'dompet' => \App\Filament\Resources\DompetResource::getUrl('index'),
                        'utang' => \App\Filament\Resources\UtangResource::getUrl('index'),
                        'piutang' => \App\Filament\Resources\PiutangResource::getUrl('index'),
                        'langganan' => \App\Filament\Resources\LanggananResource::getUrl('index'),
                        default => null,
                    };
                @endphp
                <x-filament::section :heading="\App\Filament\Pages\Dashboard::SECTIONS[$key]" wire:key="dashboard-{{ $key }}" collapsible compact :icon="$icon" data-section="{{ $key }}" :class="'dashboard-card !rounded-xl border-t-[3px] border-t-[color:var(--accent)] !shadow-[0_4px_20px_-12px_rgb(15_23_42_/_0.22)] overflow-hidden [&:is(.dark_*)]:!shadow-[0_4px_20px_-12px_rgb(0_0_0_/_0.45)] [--accent:#6366f1] [&[data-section=kas]]:[--accent:#10b981] [&[data-section=dompet]]:[--accent:#3b82f6] [&[data-section=utang]]:[--accent:#f43f5e] [&[data-section=piutang]]:[--accent:#14b8a6] [&[data-section=langganan]]:[--accent:#f59e0b] [&_.fi-section-header]:bg-[linear-gradient(120deg,color-mix(in_srgb,var(--accent)_7%,transparent),transparent)] [&_.fi-section-header>svg]:!text-[color:var(--accent)] [&_.fi-section-header>svg]:p-1.5 [&_.fi-section-header>svg]:!size-8 [&_.fi-section-header>svg]:rounded-lg [&_.fi-section-header>svg]:bg-[color-mix(in_srgb,var(--accent)_10%,transparent)] '.($key === 'transaksi' ? 'dashboard-full-width col-span-full' : '')">
                    @if ($key === 'transaksi')
                        <div class="dashboard-actions mb-3 flex flex-wrap justify-end gap-2">
                            {{ $this->tambahTransaksiAction }}
                            <x-filament::button tag="a" :href="\App\Filament\Resources\TransaksiResource::getUrl()" color="gray" icon="heroicon-o-arrow-top-right-on-square">Lihat lengkap</x-filament::button>
                        </div>
                        {{ $this->table }}
                    @elseif (in_array($key, ['kas', 'dompet']))
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Total saldo {{ $key }}</p>
                        <p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $data->sum('saldo'), 0, ',', '.') }}</p>
                        <p class="dashboard-caption mt-1 text-xs leading-normal text-[color:var(--dash-muted)]">Tersebar di {{ $data->count() }} {{ $key }}</p>
                        <dl class="dashboard-list mt-3">
                            @forelse ($data as $item)
                                <div class="dashboard-row flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[color:var(--dash-line)] py-2 last:border-b-0 last:pb-0 [&_dt]:min-w-0 [&_dt]:flex-[1_1_7rem] [&_dt]:text-sm [&_dt]:font-medium [&_dt]:[overflow-wrap:anywhere] [&_dd]:max-w-full [&_dd]:text-sm [&_dd]:font-[650] [&_dd]:tabular-nums [&_dd]:[overflow-wrap:anywhere] [&_.dashboard-caption]:text-[.7rem] [&_.dashboard-caption]:font-normal">
                                    <dt>{{ $key === 'kas' ? $item->nama_buku : $item->nama_dompet }}</dt>
                                    <dd>Rp {{ number_format((float) $item->saldo, 0, ',', '.') }}</dd>
                                </div>
                            @empty
                                <p class="dashboard-empty rounded-lg bg-[var(--dash-bg)] p-3 text-center text-sm text-[color:var(--dash-muted)]">Belum ada {{ $key }}.</p>
                            @endforelse
                        </dl>
                    @elseif (in_array($key, ['utang', 'piutang']))
                        <p class="dashboard-eyebrow mb-1 text-[.7rem] font-semibold uppercase tracking-[.08em] text-[color:var(--dash-muted)]">Total sisa {{ $key }}</p><p class="dashboard-amount text-[clamp(1.25rem,2vw,1.75rem)] leading-[1.25] font-[750] tracking-[-.04em] tabular-nums [overflow-wrap:anywhere]">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</p>
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
                                <p class="dashboard-empty rounded-lg bg-[var(--dash-bg)] p-3 text-center text-sm text-[color:var(--dash-muted)]">Belum ada {{ $key }}.</p>
                            @endforelse
                        </dl>
                    @elseif ($key === 'langganan')
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
                            <x-filament::button tag="a" :href="$manageUrl" color="gray" icon="heroicon-o-cog-6-tooth" :aria-label="'Kelola '.\App\Filament\Pages\Dashboard::SECTIONS[$key]">Kelola</x-filament::button>
                        </div>
                    @endif
                </x-filament::section>
            @empty
                <x-filament::section heading="Dashboard disembunyikan" compact class="dashboard-full-width col-span-full">
                    Aktifkan bagian yang ingin ditampilkan melalui tombol Atur dashboard.
                </x-filament::section>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
