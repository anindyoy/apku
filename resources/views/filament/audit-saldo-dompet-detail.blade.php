<div class="space-y-4">
    <div>
        <div class="text-sm text-gray-500">Catatan</div>
        <div>{{ $audit->catatan }}</div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="py-2">Dompet</th>
                    <th class="py-2 text-right">Saldo aplikasi</th>
                    <th class="py-2 text-right">Saldo riil</th>
                    <th class="py-2 text-right">Selisih</th>
                    <th class="py-2">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($audit->detail as $detail)
                    <tr class="border-b">
                        <td class="py-2">{{ $detail->nama_dompet }}</td>
                        <td class="py-2 text-right">Rp {{ number_format($detail->saldo_aplikasi, 0, ',', '.') }}</td>
                        <td class="py-2 text-right">Rp {{ number_format($detail->saldo_riil, 0, ',', '.') }}</td>
                        <td class="py-2 text-right">Rp {{ number_format($detail->selisih, 0, ',', '.') }}</td>
                        <td class="py-2">{{ $detail->catatan ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
