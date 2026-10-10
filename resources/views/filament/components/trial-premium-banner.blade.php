@php
    // Banner status trial: hijau default, kuning saat sisa <= 7 hari, merah saat sisa <= 1 hari.
    $sisa = (int) ($trial['sisa'] ?? 0);
    $warna = $sisa <= 1
        ? 'bg-red-100 text-red-900 [.dark_&]:bg-red-950 [.dark_&]:text-red-200'
        : ($sisa <= 7
            ? 'bg-yellow-100 text-yellow-900 [.dark_&]:bg-yellow-950 [.dark_&]:text-yellow-200'
            : 'bg-teal-100 text-teal-900 [.dark_&]:bg-teal-950 [.dark_&]:text-teal-200');
    $teksSisa = $sisa === 0 ? 'Berakhir hari ini' : $sisa.' hari tersisa';
@endphp

<div
    data-testid="trial-premium-banner"
    @class(['mb-3 rounded-lg px-4 py-3 text-sm font-medium leading-6', $warna])
    role="status"
>
    Trial Premium · {{ $teksSisa }} · sampai {{ $trial['berakhir'] ?? '-' }}.
    Kas/dompet ke-3 dan seterusnya terkunci setelah trial berakhir, datanya tetap tersimpan.
</div>
