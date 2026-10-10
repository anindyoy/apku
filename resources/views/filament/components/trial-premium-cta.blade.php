@php
    // Kartu CTA dipakai ulang di Dashboard dan halaman Langganan.
    $durasi = (int) ($info['durasi'] ?? 30);
    $trial = $info['trial'] ?? null;
@endphp

@if ($trial)
    @include('filament.components.trial-premium-banner', ['trial' => [
        'sisa' => $trial->sisaHari(),
        'berakhir' => $trial->berakhir_pada?->translatedFormat('d F Y'),
    ]])
@elseif (($info['layak'] ?? false))
    <div class="mb-4 rounded-lg bg-white p-4 ring-1 ring-slate-200 [&:is(.dark_*)]:bg-gray-900 [&:is(.dark_*)]:ring-slate-700" data-testid="trial-premium-cta">
        <p class="text-sm font-semibold text-slate-800 [&:is(.dark_*)]:text-slate-100">Coba Premium gratis {{ $durasi }} hari</p>
        <p class="mt-1 text-xs leading-normal text-slate-500 [&:is(.dark_*)]:text-slate-400">Kas dan dompet tanpa batas, kolaborasi kas, dan tautan kas publik. Kas/dompet ke-3+ terkunci setelah trial berakhir, datanya tetap tersimpan.</p>
        <div class="mt-2">{{ $this->mulaiTrialPremiumAction }}</div>
    </div>
@elseif (! empty($info['alasan'] ?? null))
    <p class="mb-4 text-xs leading-normal text-slate-500 [&:is(.dark_*)]:text-slate-400" data-testid="trial-premium-guidance">{{ $info['alasan'] }}</p>
@endif
