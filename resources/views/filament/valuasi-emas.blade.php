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

<div class="space-y-3">
    @if ($error)
        <div class="rounded-lg border border-danger-300 p-3 text-danger-600">{{ $error }}</div>
    @else
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div><dt class="text-sm text-gray-500">Total berat</dt><dd class="font-semibold">{{ number_format($nilai['berat_gram'], 4, ',', '.') }} gram</dd></div>
            <div><dt class="text-sm text-gray-500">Harga buyback</dt><dd class="font-semibold">Rp {{ number_format($harga['harga_per_gram'], 0, ',', '.') }}/gram</dd></div>
            <div><dt class="text-sm text-gray-500">Nilai emas</dt><dd class="font-semibold">Rp {{ number_format($nilai['nilai_emas'], 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-gray-500">Total modal</dt><dd class="font-semibold">Rp {{ number_format($nilai['total_modal'], 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-gray-500">Saldo rupiah kas</dt><dd class="font-semibold">Rp {{ number_format($nilai['saldo_rupiah'], 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-gray-500">Untung/rugi belum terealisasi</dt><dd class="font-semibold">Rp {{ number_format($nilai['untung_rugi'], 0, ',', '.') }}</dd></div>
            <div class="sm:col-span-2 rounded-lg bg-primary-50 p-3"><dt class="text-sm text-gray-500">Total nilai kas</dt><dd class="text-lg font-bold">Rp {{ number_format($nilai['total_nilai_kas'], 0, ',', '.') }}</dd></div>
        </dl>
        <p class="text-xs text-gray-500">Sumber: {{ $harga['provider'] }} · Status: {{ $harga['status'] }} · Berlaku {{ $harga['berlaku_pada']->format('d M Y H:i') }}</p>
    @endif
</div>
