<x-filament-panels::page>
    @unless (auth()->user()->isAdmin())
        <style>
            .subscription-plans { --plan-border: #e5e7eb; --plan-muted: #4b5563; --plan-text: #111827; --plan-surface: #fff; color: var(--plan-text); }
            .subscription-plans .plan-intro { max-width: 42rem; margin-bottom: 1.25rem; }
            .subscription-plans .plan-intro h2 { font-size: 1.25rem; font-weight: 700; letter-spacing: -.025em; }
            .subscription-plans .plan-description { color: var(--plan-muted); font-size: .875rem; line-height: 1.65; margin-top: .5rem; }
            .subscription-plans .plan-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
            .subscription-plans .plan-card { padding: 1.5rem; border: 1px solid var(--plan-border); border-radius: 1rem; background: var(--plan-surface); min-width: 0; }
            .subscription-plans .plan-premium { background: linear-gradient(135deg, #fffbeb, #fff 75%); border-color: #fbbf24; box-shadow: 0 4px 20px #d977060a; }
            .subscription-plans .plan-heading { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
            .subscription-plans .plan-icon { display: grid; place-items: center; width: 2.75rem; height: 2.75rem; border-radius: .75rem; background: #f3f4f6; color: #4b5563; }
            .subscription-plans svg { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
            .subscription-plans .plan-premium .plan-icon { background: #fef3c7; color: #92400e; }
            .subscription-plans h3 { font-size: 1.125rem; font-weight: 700; }
            .subscription-plans .plan-badge { border-radius: 999px; background: #fef3c7; color: #92400e; padding: .25rem .625rem; font-size: .75rem; font-weight: 600; margin-left: auto; }
            .subscription-plans .plan-features { display: grid; gap: 1rem; border-top: 1px solid var(--plan-border); padding-top: 1.25rem; margin-top: 1.25rem; }
            .subscription-plans .plan-features li { display: flex; align-items: flex-start; gap: .75rem; font-size: .875rem; line-height: 1.5; }
            .subscription-plans .plan-features svg { color: #059669; margin-top: .125rem; }
            .subscription-plans .plan-features strong { display: block; font-weight: 600; }
            .subscription-plans .plan-features span { color: var(--plan-muted); }
            .subscription-plans .plan-features .plan-limit svg { color: #9ca3af; }
            .subscription-plans .plan-shared { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; margin-top: 1rem; padding: 1rem 1.25rem; border: 1px solid var(--plan-border); border-radius: .75rem; color: var(--plan-muted); font-size: .8125rem; }
            .subscription-plans .plan-shared span { display: inline-flex; align-items: center; gap: .5rem; }
            .dark .subscription-plans { --plan-border: #374151; --plan-muted: #d1d5db; --plan-text: #f9fafb; --plan-surface: #ffffff05; }
            .dark .subscription-plans .plan-premium { background: linear-gradient(135deg, #78350f30, #ffffff05 75%); border-color: #a16207; }
            .dark .subscription-plans .plan-icon { background: #374151; color: #d1d5db; }
            .dark .subscription-plans .plan-premium .plan-icon, .dark .subscription-plans .plan-badge { background: #78350f60; color: #fde68a; }
            .dark .subscription-plans .plan-features svg { color: #34d399; }
            .dark .subscription-plans .plan-features .plan-limit svg { color: #9ca3af; }
            @media (min-width: 768px) { .subscription-plans .plan-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        </style>

        <section class="subscription-plans" aria-labelledby="subscription-plans-title">
            <div class="plan-intro">
                <h2 id="subscription-plans-title">Pilih akun yang sesuai kebutuhan Anda</h2>
                <p class="plan-description">Mulai dari kebutuhan sehari-hari, hingga keuangan usaha dan tabungan. Temukan ruang yang pas untuk mengatur keuangan Anda.</p>
            </div>

            <div class="plan-grid">
                <article class="plan-card" aria-labelledby="plan-reguler-title" data-plan="reguler">
                    <div class="plan-heading">
                        <div class="plan-icon"><x-heroicon-o-wallet aria-hidden="true" /></div>
                        <h3 id="plan-reguler-title">Reguler</h3>
                    </div>
                    <p class="plan-description">Untuk mencatat dan mengelola keuangan harian.</p>
                    <ul class="plan-features">
                        <li><x-heroicon-o-check-circle aria-hidden="true" /><div><strong>Kas utama + 1 tambahan</strong><span>Pisahkan dua kebutuhan keuangan Anda.</span></div></li>
                        <li><x-heroicon-o-check-circle aria-hidden="true" /><div><strong>Dompet utama + 1 tambahan</strong><span>Catat uang dari dua tempat penyimpanan.</span></div></li>
                        <li class="plan-limit"><x-heroicon-o-minus-circle aria-hidden="true" /><div><strong>Akses tambahan terbatas</strong><span>Pengelolaan transaksi pada kas dan dompet di luar kuota Reguler dibatasi.</span></div></li>
                    </ul>
                </article>

                <article class="plan-card plan-premium" aria-labelledby="plan-premium-title" data-plan="premium">
                    <div class="plan-heading">
                        <div class="plan-icon"><x-heroicon-o-sparkles aria-hidden="true" /></div>
                        <h3 id="plan-premium-title">Premium</h3>
                        <span class="plan-badge">Lebih leluasa</span>
                    </div>
                    <p class="plan-description">Untuk kebutuhan pribadi, usaha, dan tabungan yang terus berkembang.</p>
                    <ul class="plan-features">
                        <li><x-heroicon-o-check-circle aria-hidden="true" /><div><strong>Bebas menambah kas</strong><span>Kelompokkan keuangan sesuai kebutuhan Anda.</span></div></li>
                        <li><x-heroicon-o-check-circle aria-hidden="true" /><div><strong>Bebas menambah dompet</strong><span>Kelola lebih banyak rekening dan tempat penyimpanan.</span></div></li>
                        <li><x-heroicon-o-check-circle aria-hidden="true" /><div><strong>Akses penuh selama Premium aktif</strong><span>Kelola transaksi pada seluruh kas dan dompet tambahan.</span></div></li>
                    </ul>
                </article>
            </div>

            <div class="plan-shared">
                <strong>Tersedia di kedua akun</strong>
                <span><x-heroicon-o-document-chart-bar aria-hidden="true" /> Laporan dan ekspor PDF/Excel</span>
                <span><x-heroicon-o-arrows-right-left aria-hidden="true" /> Utang dan piutang</span>
            </div>
        </section>
    @endunless

    {{ $this->table }}
</x-filament-panels::page>
