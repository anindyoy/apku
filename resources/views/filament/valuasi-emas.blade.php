@php
    try {
        $harga = app(\App\Services\HargaEmasService::class)->hargaBuyback($record, true);
        $nilai = app(\App\Services\TabunganEmasService::class)->valuasi($record, $harga['harga_per_gram']);
        $error = null;
    } catch (\Throwable $exception) {
        $harga = $nilai = null;
        $error = $exception->getMessage();
    }
@endphp

<style>
    .valuasi-emas { --emas-border: #e5e7eb; --emas-muted: #6b7280; --emas-text: #111827; --emas-surface: #f9fafb; color: var(--emas-text); display: grid; gap: 1.25rem; }
    .valuasi-emas .emas-hero { padding: 1.5rem; border: 1px solid #f3d89c; border-radius: 1rem; background: linear-gradient(120deg, #fffbeb, #fef3c7); color: #78350f; }
    .valuasi-emas .emas-eyebrow { display: flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 600; }
    .valuasi-emas svg { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
    .valuasi-emas .emas-total { margin-top: .5rem; font-size: clamp(1.6rem, 5vw, 2.25rem); font-weight: 750; letter-spacing: -.035em; line-height: 1.2; overflow-wrap: anywhere; }
    .valuasi-emas .emas-caption { margin-top: .5rem; font-size: .8rem; line-height: 1.6; }
    .valuasi-emas .emas-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; }
    .valuasi-emas .emas-card { border: 1px solid var(--emas-border); border-radius: .85rem; padding: 1rem; min-width: 0; }
    .valuasi-emas dt, .valuasi-emas .emas-meta { font-size: .8rem; color: var(--emas-muted); line-height: 1.6; }
    .valuasi-emas dd { margin-top: .3rem; font-size: 1.1rem; font-weight: 650; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .valuasi-emas .emas-details { padding: .25rem 1rem; border-radius: .85rem; background: var(--emas-surface); }
    .valuasi-emas .emas-row { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .25rem 1rem; padding: .75rem 0; }
    .valuasi-emas .emas-row + .emas-row { border-top: 1px solid var(--emas-border); }
    .valuasi-emas .emas-row dd { font-size: .9rem; }
    .valuasi-emas .emas-result { border-left: 3px solid currentColor; border-radius: .25rem; padding-left: .85rem; }
    .valuasi-emas .emas-result[data-trend="profit"] { color: #047857; }
    .valuasi-emas .emas-result[data-trend="loss"], .valuasi-emas .emas-error { color: #be123c; }
    .valuasi-emas .emas-footer { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; border-top: 1px solid var(--emas-border); padding-top: 1rem; }
    .valuasi-emas .emas-source { text-decoration: underline; text-underline-offset: .2em; }
    .valuasi-emas .emas-source:hover { color: var(--emas-text); }
    .valuasi-emas .emas-badge { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .25rem .65rem; background: var(--emas-surface); color: var(--emas-text); font-size: .75rem; font-weight: 600; }
    .valuasi-emas .emas-error { padding: 1rem; border: 1px solid currentColor; border-radius: .85rem; display: flex; align-items: flex-start; gap: .75rem; }
    .dark .valuasi-emas { --emas-border: #374151; --emas-muted: #9ca3af; --emas-text: #f3f4f6; --emas-surface: #ffffff08; }
    .dark .valuasi-emas .emas-hero { background: linear-gradient(120deg, #78350f40, #92400e20); border-color: #a1620759; color: #fde68a; }
    .dark .valuasi-emas .emas-result[data-trend="profit"] { color: #6ee7b7; }
    .dark .valuasi-emas .emas-result[data-trend="loss"], .dark .valuasi-emas .emas-error { color: #fda4af; }
    @media (min-width: 640px) { .valuasi-emas .emas-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="valuasi-emas">
    @if ($error)
        <div class="emas-error" role="alert">
            <x-heroicon-o-exclamation-circle />
            <div><p class="font-semibold">Nilai emas belum dapat ditampilkan</p><p class="emas-caption">{{ $error }}</p></div>
        </div>
    @else
        <div class="emas-hero"><dl>
            <dt class="emas-eyebrow" style="color: inherit"><x-heroicon-o-wallet /> Total nilai kas</dt>
            <dd class="emas-total" data-value="total">Rp {{ number_format($nilai['total_nilai_kas'], 0, ',', '.') }}</dd>
            </dl><p class="emas-caption">Gabungan saldo rupiah dan estimasi nilai jual emas Anda.</p>
        </div>

        <dl class="emas-grid">
            <div class="emas-card"><dt>Nilai emas</dt><dd data-value="gold">Rp {{ number_format($nilai['nilai_emas'], 0, ',', '.') }}</dd></div>
            <div class="emas-card"><dt>Saldo rupiah kas</dt><dd data-value="cash">Rp {{ number_format($nilai['saldo_rupiah'], 0, ',', '.') }}</dd></div>
        </dl>

        <dl class="emas-details">
            <div class="emas-row"><dt>Total berat</dt><dd>{{ number_format($nilai['berat_gram'], 4, ',', '.') }} gram</dd></div>
            <div class="emas-row"><dt>Harga buyback</dt><dd>Rp {{ number_format($harga['harga_per_gram'], 0, ',', '.') }} / gram</dd></div>
            <div class="emas-row"><dt>Total modal</dt><dd>Rp {{ number_format($nilai['total_modal'], 0, ',', '.') }}</dd></div>
        </dl>

        <div class="emas-result" data-trend="{{ $nilai['untung_rugi'] > 0 ? 'profit' : ($nilai['untung_rugi'] < 0 ? 'loss' : 'neutral') }}"><dl>
            <dt>{{ $nilai['untung_rugi'] < 0 ? 'Estimasi rugi' : ($nilai['untung_rugi'] > 0 ? 'Estimasi untung' : 'Untung/rugi') }} belum terealisasi</dt>
            <dd data-value="result">{{ $nilai['untung_rugi'] > 0 ? '+' : ($nilai['untung_rugi'] < 0 ? '−' : '') }}Rp {{ number_format(abs($nilai['untung_rugi']), 0, ',', '.') }}</dd>
            </dl><p class="emas-meta">Selisih nilai emas dan modal. Belum dicatat sebagai transaksi.</p>
        </div>

        <div class="emas-footer">
            <span class="emas-badge"><x-heroicon-o-clock />{{ match ($harga['status']) { 'terbaru' => 'Harga terbaru', 'manual' => 'Harga manual', 'cache' => 'Harga tersimpan', default => $harga['status'] } }}</span>
            <span class="emas-meta">Sumber:
                @if ($harga['status'] === 'manual')
                    {{ $harga['provider'] }}
                @else
                    <a class="emas-source" href="{{ app(\App\Services\PengaturanHargaEmas::class)->semua()['source'] }}" target="_blank" rel="noopener noreferrer">{{ $harga['provider'] }}</a>
                @endif
            </span>
            <span class="emas-meta">Berlaku {{ $harga['berlaku_pada']->format('d M Y H:i') }}</span>
        </div>
    @endif
</div>
