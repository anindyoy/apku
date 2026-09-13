<x-filament-panels::page>
    <style>
        .aktivitas-page { --aktivitas-border: #e2e8f0; --aktivitas-muted: #64748b; --aktivitas-surface: #fff; display: grid; gap: 1.5rem; }
        .aktivitas-intro { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border: 1px solid var(--aktivitas-border); border-radius: 1rem; background: var(--aktivitas-surface); }
        .aktivitas-intro > svg { width: 2.75rem; height: 2.75rem; flex-shrink: 0; padding: .65rem; border-radius: .85rem; color: #6366f1; background: rgb(99 102 241 / .1); }
        .aktivitas-intro h2 { font-size: .95rem; font-weight: 650; }
        .aktivitas-page p { color: var(--aktivitas-muted); font-size: .875rem; line-height: 1.65; margin-top: .25rem; }
        .aktivitas-grid { display: grid; grid-template-columns: minmax(0, 1fr); align-items: start; gap: 1.5rem; }
        .aktivitas-panel { --aktivitas-accent: #059669; min-width: 0; border: 1px solid var(--aktivitas-border); border-top: 3px solid var(--aktivitas-accent); border-radius: 1.25rem; background: var(--aktivitas-surface); box-shadow: 0 4px 20px -12px rgb(15 23 42 / .2); }
        .aktivitas-panel--pengeluaran { --aktivitas-accent: #e11d48; }
        .aktivitas-panel-header { display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; border-radius: 1.1rem 1.1rem 0 0; background: linear-gradient(120deg, color-mix(in srgb, var(--aktivitas-accent) 8%, transparent), transparent); }
        .aktivitas-panel-header > svg { width: 2.75rem; height: 2.75rem; flex-shrink: 0; padding: .65rem; border-radius: .85rem; color: var(--aktivitas-accent); background: color-mix(in srgb, var(--aktivitas-accent) 12%, transparent); }
        .aktivitas-panel h2 { color: var(--aktivitas-accent); font-size: 1.125rem; font-weight: 700; }
        .aktivitas-panel-body { padding: 0 1rem 1rem; }
        .aktivitas-tip { display: flex; align-items: flex-start; gap: .6rem; color: var(--aktivitas-muted); font-size: .8rem; line-height: 1.65; }
        .aktivitas-tip > svg { width: 1.15rem; height: 1.15rem; flex-shrink: 0; margin-top: .1rem; }
        .dark .aktivitas-page { --aktivitas-border: #334155; --aktivitas-muted: #94a3b8; --aktivitas-surface: #18181b; }
        .dark .aktivitas-panel { --aktivitas-accent: #34d399; }
        .dark .aktivitas-panel--pengeluaran { --aktivitas-accent: #fb7185; }
        @media (min-width: 1280px) { .aktivitas-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 639px) { .aktivitas-intro, .aktivitas-panel-header { padding: 1rem; } .aktivitas-panel-body { padding: 0 .5rem .5rem; } }
    </style>

    <div class="aktivitas-page">

        <div class="aktivitas-grid">
            <section class="aktivitas-panel" aria-labelledby="aktivitas-pemasukan-title">
                <header class="aktivitas-panel-header">
                    <x-heroicon-o-arrow-down-left aria-hidden="true" />
                    <div>
                        <h2 id="aktivitas-pemasukan-title">Pemasukan</h2>
                        <p>Sumber uang masuk, seperti gaji, bonus, atau hasil usaha.</p>
                    </div>
                </header>
                <div class="aktivitas-panel-body">
                    @livewire('kategori.pemasukan')
                </div>
            </section>

            <section class="aktivitas-panel aktivitas-panel--pengeluaran" aria-labelledby="aktivitas-pengeluaran-title">
                <header class="aktivitas-panel-header">
                    <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    <div>
                        <h2 id="aktivitas-pengeluaran-title">Pengeluaran</h2>
                        <p>Kebutuhan uang keluar, seperti belanja, transportasi, atau tagihan.</p>
                    </div>
                </header>
                <div class="aktivitas-panel-body">
                    @livewire('kategori.pengeluaran')
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
