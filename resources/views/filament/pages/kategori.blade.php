<x-filament-panels::page>
    @vite('resources/css/filament-toolbar.css')
    <style>
        .kategori-page { --kategori-border: #e2e8f0; --kategori-muted: #64748b; --kategori-surface: #fff; display: grid; gap: 1.5rem; }
        .kategori-intro { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border: 1px solid var(--kategori-border); border-radius: 1rem; background: var(--kategori-surface); }
        .kategori-intro > svg { width: 2.75rem; height: 2.75rem; flex-shrink: 0; padding: .65rem; border-radius: .85rem; color: #6366f1; background: rgb(99 102 241 / .1); }
        .kategori-intro h2 { font-size: .95rem; font-weight: 650; }
        .kategori-page p { color: var(--kategori-muted); font-size: .875rem; line-height: 1.65; margin-top: .25rem; }
        .kategori-grid { display: grid; grid-template-columns: minmax(0, 1fr); align-items: start; gap: 1.5rem; }
        .kategori-panel { --kategori-accent: #059669; min-width: 0; border: 1px solid var(--kategori-border); border-top: 3px solid var(--kategori-accent); border-radius: 1.25rem; background: var(--kategori-surface); box-shadow: 0 4px 20px -12px rgb(15 23 42 / .2); }
        .kategori-panel--pengeluaran { --kategori-accent: #e11d48; }
        .kategori-panel-header { display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; border-radius: 1.1rem 1.1rem 0 0; background: linear-gradient(120deg, color-mix(in srgb, var(--kategori-accent) 8%, transparent), transparent); }
        .kategori-panel-header > svg { width: 2.75rem; height: 2.75rem; flex-shrink: 0; padding: .65rem; border-radius: .85rem; color: var(--kategori-accent); background: color-mix(in srgb, var(--kategori-accent) 12%, transparent); }
        .kategori-panel h2 { color: var(--kategori-accent); font-size: 1.125rem; font-weight: 700; }
        .kategori-panel-body { padding: 0 1rem 1rem; }
        .kategori-tip { display: flex; align-items: flex-start; gap: .6rem; color: var(--kategori-muted); font-size: .8rem; line-height: 1.65; }
        .kategori-tip > svg { width: 1.15rem; height: 1.15rem; flex-shrink: 0; margin-top: .1rem; }
        .dark .kategori-page { --kategori-border: #334155; --kategori-muted: #94a3b8; --kategori-surface: #18181b; }
        .dark .kategori-panel { --kategori-accent: #34d399; }
        .dark .kategori-panel--pengeluaran { --kategori-accent: #fb7185; }
        @media (min-width: 1280px) { .kategori-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 639px) { .kategori-intro, .kategori-panel-header { padding: 1rem; } .kategori-panel-body { padding: 0 .5rem .5rem; } }
    </style>

    <div class="kategori-page">

        <div class="kategori-grid">
            <section class="kategori-panel" aria-labelledby="kategori-pemasukan-title">
                <header class="kategori-panel-header">
                    <x-heroicon-o-arrow-down-left aria-hidden="true" />
                    <div>
                        <h2 id="kategori-pemasukan-title">Pemasukan</h2>
                        <p>Sumber uang masuk, seperti gaji, bonus, atau hasil usaha.</p>
                    </div>
                </header>
                <div class="kategori-panel-body mt-4">
                    @livewire('kategori.pemasukan')
                </div>
            </section>

            <section class="kategori-panel kategori-panel--pengeluaran" aria-labelledby="kategori-pengeluaran-title">
                <header class="kategori-panel-header">
                    <x-heroicon-o-arrow-up-right aria-hidden="true" />
                    <div>
                        <h2 id="kategori-pengeluaran-title">Pengeluaran</h2>
                        <p>Kebutuhan uang keluar, seperti belanja, transportasi, atau tagihan.</p>
                    </div>
                </header>
                <div class="kategori-panel-body mt-4">
                    @livewire('kategori.pengeluaran')
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
