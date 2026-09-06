<div class="space-y-3">
    @forelse ($items as $item)
        <div class="rounded-lg border p-3">
            <div class="font-medium">{{ ucfirst($item->jenis) }} · {{ number_format((float) $item->berat_gram, 4, ',', '.') }} gram</div>
            <div class="text-sm text-gray-500">
                {{ $item->tanggal->format('d M Y H:i') }} · {{ $item->user->name }}
                @if ($item->total_rupiah !== null)
                    · Rp {{ number_format($item->total_rupiah, 0, ',', '.') }}
                @endif
            </div>
            @if ($item->catatan)
                <div class="mt-1 text-sm">{{ $item->catatan }}</div>
            @endif
        </div>
    @empty
        <p>Belum ada histori emas.</p>
    @endforelse
</div>
