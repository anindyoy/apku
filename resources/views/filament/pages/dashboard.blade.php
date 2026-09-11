<x-filament-panels::page>
    @if (auth()->user()->isAdmin())
        {{ $this->content }}
    @else
        <style>
            .dashboard-user-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
            .dashboard-user-grid > * { min-width: 0; }
            .dashboard-user-grid .dashboard-full-width { grid-column: 1 / -1; }
            .dashboard-user-grid { --dash-muted: #64748b; --dash-line: #e2e8f0; --dash-bg: #f8fafc; }
            .dashboard-card { --accent: #6366f1; border-radius: 1.25rem; border-top: 3px solid var(--accent); box-shadow: 0 4px 20px -12px rgb(15 23 42 / .22); overflow: hidden; }
            .dashboard-card[data-section='kas'] { --accent: #10b981; }
            .dashboard-card[data-section='dompet'] { --accent: #3b82f6; }
            .dashboard-card[data-section='utang'] { --accent: #f43f5e; }
            .dashboard-card[data-section='piutang'] { --accent: #14b8a6; }
            .dashboard-card[data-section='langganan'] { --accent: #f59e0b; }
            .dashboard-card .fi-section-header { background: linear-gradient(120deg, color-mix(in srgb, var(--accent) 7%, transparent), transparent); }
            .dashboard-card .fi-section-header > svg { color: var(--accent); padding: .55rem; width: 2.75rem; height: 2.75rem; border-radius: .85rem; background: color-mix(in srgb, var(--accent) 10%, transparent); }
            .dashboard-eyebrow { color: var(--dash-muted); font-size: .7rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .4rem; }
            .dashboard-amount { font-size: clamp(1.45rem, 2.2vw, 2rem); line-height: 1.25; font-weight: 750; letter-spacing: -.04em; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
            .dashboard-caption { color: var(--dash-muted); font-size: .75rem; line-height: 1.6; margin-top: .4rem; }
            .dashboard-list { margin-top: 1.25rem; }
            .dashboard-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem; padding: .9rem 0; border-bottom: 1px solid var(--dash-line); }
            .dashboard-row:last-child { border-bottom: 0; padding-bottom: 0; }
            .dashboard-row dt { flex: 1 1 7rem; min-width: 0; font-size: .875rem; font-weight: 500; overflow-wrap: anywhere; }
            .dashboard-row dd { font-size: .875rem; font-weight: 650; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; max-width: 100%; }
            .dashboard-row .dashboard-caption { font-size: .7rem; font-weight: 400; }
            .dashboard-empty { padding: 1.25rem; border-radius: .85rem; background: var(--dash-bg); color: var(--dash-muted); font-size: .875rem; text-align: center; }
            .dashboard-membership { padding: 1.25rem; border-radius: 1rem; background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 12%, transparent), var(--dash-bg)); }
            .dashboard-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.25rem; }
            .dashboard-footer { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--dash-line); }
            .dark .dashboard-user-grid { --dash-muted: #94a3b8; --dash-line: #334155; --dash-bg: #1e293b; }
            .dark .dashboard-card { box-shadow: 0 4px 20px -12px rgb(0 0 0 / .45); }
            @media (min-width: 768px) {
                .dashboard-user-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (min-width: 1280px) {
                .dashboard-user-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }
        </style>
        <div class="dashboard-user-grid">
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
                @endphp
                <x-filament::section :heading="\App\Filament\Pages\Dashboard::SECTIONS[$key]" wire:key="dashboard-{{ $key }}" collapsible :icon="$icon" data-section="{{ $key }}" :class="'dashboard-card '.($key === 'transaksi' ? 'dashboard-full-width' : '')">
                    @if ($key === 'transaksi')
                        <div class="dashboard-actions">
                            {{ $this->tambahTransaksiAction }}
                            <x-filament::button tag="a" :href="\App\Filament\Resources\TransaksiResource::getUrl()" color="gray" icon="heroicon-o-arrow-top-right-on-square">Lihat lengkap</x-filament::button>
                        </div>
                        {{ $this->table }}
                    @elseif (in_array($key, ['kas', 'dompet']))
                        <p class="dashboard-eyebrow">Total saldo {{ $key }}</p>
                        <p class="dashboard-amount">Rp {{ number_format((float) $data->sum('saldo'), 0, ',', '.') }}</p>
                        <p class="dashboard-caption">Tersebar di {{ $data->count() }} {{ $key }}</p>
                        <dl class="dashboard-list">
                            @forelse ($data as $item)
                                <div class="dashboard-row">
                                    <dt>{{ $key === 'kas' ? $item->nama_buku : $item->nama_dompet }}</dt>
                                    <dd>Rp {{ number_format((float) $item->saldo, 0, ',', '.') }}</dd>
                                </div>
                            @empty
                                <p class="dashboard-empty">Belum ada {{ $key }}.</p>
                            @endforelse
                        </dl>
                    @elseif (in_array($key, ['utang', 'piutang']))
                        <p class="dashboard-eyebrow">Total sisa {{ $key }}</p><p class="dashboard-amount">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</p>
                        <p class="dashboard-caption">3 catatan dengan aktivitas terbaru</p>
                        <dl class="dashboard-list">
                            @forelse ($data['latest'] as $item)
                                <div class="dashboard-row">
                                    <dt>
                                        {{ $item->kepada }}
                                        <div class="dashboard-caption">{{ $item->last_activity_date ? \Illuminate\Support\Carbon::parse($item->last_activity_date)->translatedFormat('d M Y, H:i') : $item->created_at->translatedFormat('d M Y') }} · {{ $item->nominal <= 0 ? 'Selesai' : 'Belum selesai' }}</div>
                                    </dt>
                                    <dd>Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</dd>
                                </div>
                            @empty
                                <p class="dashboard-empty">Belum ada {{ $key }}.</p>
                            @endforelse
                        </dl>
                    @elseif ($key === 'langganan')
                        <p class="dashboard-eyebrow">Langganan Anda</p><p class="dashboard-amount dashboard-membership">{{ $data['active'] ? 'Premium aktif' : 'Reguler' }}</p>
                        @if ($data['active'])
                            <p class="dashboard-caption">Aktif sampai {{ $data['expires'] }} · {{ $data['days'] === 0 ? 'Berakhir hari ini' : $data['days'].' hari tersisa' }}</p>
                        @elseif ($data['expires'])
                            <p class="dashboard-caption">Masa aktif premium berakhir pada {{ $data['expires'] }}.</p>
                        @else
                            <p class="dashboard-caption">Belum memiliki langganan premium aktif.</p>
                        @endif
                        <div class="dashboard-footer">
                            <x-filament::link :href="\App\Filament\Resources\LanggananResource::getUrl()">Kelola langganan</x-filament::link>
                        </div>
                    @endif
                </x-filament::section>
            @empty
                <x-filament::section heading="Dashboard disembunyikan" class="dashboard-full-width">
                    Aktifkan bagian yang ingin ditampilkan melalui tombol Atur dashboard.
                </x-filament::section>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
