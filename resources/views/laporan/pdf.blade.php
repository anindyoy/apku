<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px; }
        body { color: #28313b; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        h1 { margin: 0 0 4px; color: #20262d; font-size: 20px; }
        .meta { margin-bottom: 18px; color: #626c76; }
        .summary { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .summary td { width: 20%; padding: 10px; border: 1px solid #d9dee3; }
        .summary span { display: block; margin-bottom: 4px; color: #6b7280; font-size: 8px; text-transform: uppercase; }
        .summary strong { font-size: 12px; }
        table.details { width: 100%; border-collapse: collapse; }
        .details th { padding: 7px; color: white; background: #4a515a; text-align: left; }
        .details td { padding: 6px 7px; border-bottom: 1px solid #e2e5e8; vertical-align: top; }
        .details tbody tr:nth-child(even) { background: #f5f6f7; }
        .number { text-align: right; white-space: nowrap; }
        .empty { padding: 24px; color: #7b8490; text-align: center; }
        .footer { margin-top: 12px; color: #8a929a; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    @php($rupiah = fn ($nilai) => 'Rp '.number_format($nilai, 0, ',', '.'))
    <h1>Laporan Kas</h1>
    <div class="meta">{{ $namaBuku }} &bull; {{ $namaDompet }} &bull; {{ $tipePeriode }} &bull; {{ $laporan['label'] }}</div>

    <table class="summary">
        <tr>
            <td><span>Saldo Awal</span><strong>{{ $rupiah($laporan['saldoAwal']) }}</strong></td>
            <td><span>Pemasukan</span><strong>{{ $rupiah($laporan['pemasukan']) }}</strong></td>
            <td><span>Pengeluaran</span><strong>{{ $rupiah($laporan['pengeluaran']) }}</strong></td>
            <td><span>Akumulasi</span><strong>{{ $rupiah($laporan['akumulasi']) }}</strong></td>
            <td><span>Saldo Akhir</span><strong>{{ $rupiah($laporan['saldoAkhir']) }}</strong></td>
        </tr>
    </table>

    <table class="details">
        <thead><tr><th>Tanggal</th><th>Kas</th><th>Dompet</th><th>Jenis</th><th>Aktivitas</th><th>Deskripsi</th><th class="number">Nominal</th></tr></thead>
        <tbody>
            @forelse ($laporan['transaksi'] as $transaksi)
                <tr>
                    <td>{{ \Carbon\CarbonImmutable::parse($transaksi->tanggal)->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaksi->buku_kas?->nama_buku ?? '-' }}</td>
                    <td>{{ $transaksi->labelDompetUntuk(auth()->user()) }}</td>
                    <td>{{ $transaksi->jenis }}</td>
                    <td>{{ str_starts_with($transaksi->jenis, 'Transfer') ? 'Transfer' : ($transaksi->jenis_transaksi?->nama_jenis ?? 'Tanpa aktivitas') }}</td>
                    <td>{{ $transaksi->deskripsi ?: '-' }}</td>
                    <td class="number">{{ $rupiah($transaksi->nominal) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dibuat pada {{ now()->locale('id')->translatedFormat('d F Y, H:i') }}</div>
</body>
</html>
