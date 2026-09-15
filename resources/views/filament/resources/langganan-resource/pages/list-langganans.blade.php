<x-filament-panels::page>
    @unless (auth()->user()->isAdmin())
        <style>
            .subscription-plans { --plan-border: #e5e7eb; --plan-muted: #4b5563; --plan-text: #111827; --plan-surface: #fff; --plan-accent: #fffbeb; color: var(--plan-text); max-width: 52rem; }
            .subscription-plans h2 { font-size: 1.25rem; font-weight: 700; }
            .subscription-plans .plan-description { color: var(--plan-muted); font-size: .875rem; line-height: 1.65; margin-top: .5rem; }
            .subscription-plans .plan-table-wrap { margin-top: 1.25rem; overflow-x: auto; border: 1px solid var(--plan-border); border-radius: 1rem; background: var(--plan-surface); }
            .subscription-plans table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: .875rem; line-height: 1.5; }
            .subscription-plans caption { text-align: left; padding: 1rem; font-weight: 600; }
            .subscription-plans th, .subscription-plans td { padding: 1rem; border-top: 1px solid var(--plan-border); vertical-align: top; overflow-wrap: anywhere; }
            .subscription-plans th { text-align: left; font-weight: 600; }
            .subscription-plans thead th { font-size: 1rem; }
            .subscription-plans thead th:first-child { width: 40%; }
            .subscription-plans td { color: var(--plan-muted); }
            .subscription-plans tr > :last-child { background: var(--plan-accent); }
            .subscription-plans small { display: block; font-size: .75rem; font-weight: 400; color: var(--plan-muted); margin-top: .25rem; }
            .subscription-plans .plan-status { display: inline-flex; align-items: center; gap: .375rem; }
            .subscription-plans svg { width: 1rem; height: 1rem; flex-shrink: 0; color: #059669; }
            .subscription-plans .plan-notes { margin-top: 1rem; padding-left: 1.25rem; list-style: disc; color: var(--plan-muted); font-size: .8125rem; line-height: 1.65; }
            .subscription-plans .plan-notes li + li { margin-top: .375rem; }
            .subscription-plans summary { display: flex; align-items: flex-start; gap: .5rem; }
            .subscription-plans summary::before { content: '▶'; display: inline-block; font-size: .625rem; margin-top: .35rem; transition: transform .2s ease; flex-shrink: 0; }
            .subscription-plans[open] summary::before { transform: rotate(90deg); }
            .subscription-plans summary::-webkit-details-marker { display: none; }
            .subscription-plans summary::marker { display: none; content: ''; }
            .dark .subscription-plans { --plan-border: #374151; --plan-muted: #d1d5db; --plan-text: #f9fafb; --plan-surface: #ffffff05; --plan-accent: #78350f30; }
            .dark .subscription-plans svg { color: #34d399; }
            .subscription-plans .plan-unavailable svg { color: #dc2626; }
            .dark .subscription-plans .plan-unavailable svg { color: #f87171; }
            @media (max-width: 640px) {
                .subscription-plans th, .subscription-plans td { padding: .75rem .5rem; }
                .subscription-plans table { font-size: .75rem; }
                .subscription-plans thead th { font-size: .875rem; }
                .subscription-plans .plan-status { flex-wrap: wrap; }
            }
        </style>

        <details class="subscription-plans" aria-labelledby="subscription-plans-title">
            <summary id="subscription-plans-title" class="plan-intro" style="cursor: pointer; list-style: none;">
                <h2 style="margin: 0;">Bandingkan akun Free dan Premium</h2>
                <p class="plan-description">Free adalah akun Reguler gratis untuk pencatatan keuangan harian. Pilih Premium jika Anda memerlukan lebih banyak kas, dompet, dan fasilitas berbagi kas.</p>
            </summary>

            <div class="plan-table-wrap">
                <table data-testid="plan-comparison" aria-describedby="plan-access-notes">
                    <caption>Perbandingan fitur APKu</caption>
                    <thead><tr><th scope="col">Fitur</th><th scope="col">Free (Reguler)<small>Gratis</small></th><th scope="col">Premium<small>Selama masa aktif berlaku</small></th></tr></thead>
                    <tbody>
                        <tr><th scope="row">Jumlah kas</th><td>Maksimal 2</td><td>Tidak terbatas</td></tr>
                        <tr><th scope="row">Jumlah Dompet</th><td>Maksimal 2</td><td>Tidak terbatas</td></tr>
                        @foreach (['Import transaksi CSV / XLSX', 'Laporan dan ekspor PDF / Excel', 'Utang dan piutang', 'Audit saldo dompet', 'Tabungan emas dan estimasi nilai'] as $fitur)
                            <tr><th scope="row">{{ $fitur }}</th>
                                <td><span class="plan-status" role="img" aria-label="Tersedia"><x-heroicon-o-check aria-hidden="true" /></span></td>
                                <td><span class="plan-status" role="img" aria-label="Tersedia"><x-heroicon-o-check aria-hidden="true" /></span></td>
                            </tr>
                        @endforeach
                        <tr><th scope="row">Membuat kolaborasi kas (Viewer / Editor)</th><td><span class="plan-status plan-unavailable" role="img" aria-label="Tidak tersedia"><x-heroicon-o-x-mark aria-hidden="true" /></span></td><td><span class="plan-status" role="img" aria-label="Tersedia"><x-heroicon-o-check aria-hidden="true" /></span></td></tr>
                        <tr><th scope="row">Membuat link kas publik tanpa login (hanya lihat)</th><td><span class="plan-status plan-unavailable" role="img" aria-label="Tidak tersedia"><x-heroicon-o-x-mark aria-hidden="true" /></span></td><td><span class="plan-status" role="img" aria-label="Tersedia"><x-heroicon-o-check aria-hidden="true" /></span></td></tr>
                    </tbody>
                </table>
            </div>
            <ul class="plan-notes" id="plan-access-notes">
                <li>Pencatatan, transfer, import, dan audit mengikuti hak akses serta kas dan dompet yang dapat dikelola. Import dan transfer pada kas bersama hanya tersedia bagi pemilik kas.</li>
                <li>Kas bersama tidak mengurangi kuota kas sendiri. Viewer hanya melihat; Editor memakai dompet dan aktivitas sendiri serta hanya dapat mengubah atau menghapus transaksi buatannya. Editor hanya dapat mencatat pada kas yang masih dapat dikelola pemilik.</li>
                <li>Saat Premium berakhir, pengelolaan kas dan dompet kembali mengikuti kuota Reguler, dan pembuatan kolaborasi baru tidak tersedia. Kolaborasi yang sudah ada tetap dapat dikelola atau dicabut pemilik.</li>
                <li>Harga dan masa aktif Premium mengikuti paket yang dipilih saat membuat order. Aktivasi dilakukan setelah pembayaran disetujui admin.</li>
            </ul>
        </details>
    @endunless

    {{ $this->table }}
</x-filament-panels::page>
