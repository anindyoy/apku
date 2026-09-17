<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $kas->nama_buku }} · APKu</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172b28; background: #f4f7f6; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { max-width: 1100px; margin: auto; padding: 36px 20px; }
        header { margin-bottom: 28px; }
        .brand { color: #147567; font-weight: 700; letter-spacing: .12em; font-size: 14px; }
        h1 { margin: 12px 0; font-size: clamp(26px, 5vw, 38px); overflow-wrap: anywhere; }
        h2 { font-size: 20px; margin: 0; }
        p { line-height: 1.6; }
        .muted, small { color: #5a6d68; }
        .badge { display: inline-block; background: #dcefe9; color: #17604e; border-radius: 30px; padding: 6px 12px; font-size: 13px; font-weight: 600; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 24px 0; }
        .card, .panel { background: white; border: 1px solid #dce5e1; border-radius: 14px; }
        .card { padding: 22px; }
        .amount { display: block; font-size: 25px; font-weight: 700; margin-top: 10px; overflow-wrap: anywhere; }
        .incoming { color: #15734d; }
        .outgoing { color: #ba303a; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding: 22px; }
        form { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; }
        label { display: grid; gap: 6px; font-size: 13px; font-weight: 600; }
        input, button { font: inherit; padding: 10px 12px; border: 1px solid #b6c9c0; border-radius: 8px; }
        button { background: #176b56; color: white; cursor: pointer; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th, td { padding: 16px 22px; border-top: 1px solid #e4ebe7; vertical-align: top; }
        th { background: #f8faf9; font-size: 12px; color: #5a6d68; }
        td p { margin: 6px 0 0; overflow-wrap: anywhere; }
        .number { text-align: right; white-space: nowrap; }
        .date { white-space: nowrap; }
        .mobile-cell { display: none; }
        .public-mobile-main { display: grid; grid-template-columns: minmax(0, 1fr) max-content; align-items: baseline; gap: 8px; width: 100%; }
        .public-mobile-type { min-width: 0; overflow-wrap: anywhere; font-size: 13px; font-weight: 600; }
        .public-mobile-amount { text-align: right; white-space: nowrap; font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .public-mobile-detail { display: flex; flex-wrap: wrap; gap: 2px 10px; margin-top: 3px; font-size: 12px; }
        .public-mobile-activity { color: #374151; font-weight: 500; overflow-wrap: anywhere; }
        .public-mobile-date { color: #6b7280; white-space: nowrap; }
        .public-mobile-description { margin: 3px 0 0; color: #5a6d68; font-size: 12px; overflow-wrap: anywhere; }
        .public-mobile-summary[data-tone="success"] .public-mobile-type,
        .public-mobile-summary[data-tone="success"] .public-mobile-amount { color: #15803d; }
        .public-mobile-summary[data-tone="danger"] .public-mobile-type,
        .public-mobile-summary[data-tone="danger"] .public-mobile-amount { color: #b91c1c; }
        .public-mobile-summary[data-tone="info"] .public-mobile-type,
        .public-mobile-summary[data-tone="info"] .public-mobile-amount { color: #1d4ed8; }
        .public-mobile-summary[data-tone="warning"] .public-mobile-type,
        .public-mobile-summary[data-tone="warning"] .public-mobile-amount { color: #a16207; }
        nav { padding: 20px 22px; display: flex; gap: 20px; flex-wrap: wrap; }
        a { color: #176b56; }
        footer { margin-top: 20px; font-size: 13px; }
        @media (max-width: 650px) {
            .summary { grid-template-columns: 1fr; gap: 10px; }
            .card { padding: 18px; }
            .table-wrap { overflow-x: visible; }
            table, tbody, tr { display: block; }
            thead, .desktop-cell { display: none; }
            tbody tr { border-top: 1px solid #e4ebe7; }
            tbody tr:first-child { border-top: 0; }
            .mobile-cell { display: block; padding: 11px 14px; border-top: 0; }
            tbody tr > td[colspan] { display: block; padding: 14px; border-top: 0; }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div class="brand">APKu / KAS PUBLIK</div>
        <h1>{{ $kas->nama_buku }}</h1>
        <span class="badge">Viewer · Hanya lihat</span>
        @if ($kas->description)
            <p class="muted">{{ $kas->description }}</p>
        @endif
    </header>
    <section class="summary" aria-label="Ringkasan kas">
        <div class="card"><small>Saldo kas saat ini</small><strong class="amount">Rp {{ number_format($kas->saldo, 0, ',', '.') }}</strong></div>
        <div class="card"><small>Pemasukan bulan {{ $bulan }}</small><strong class="amount incoming">Rp {{ number_format($pemasukan, 0, ',', '.') }}</strong></div>
        <div class="card"><small>Pengeluaran bulan {{ $bulan }}</small><strong class="amount outgoing">Rp {{ number_format($pengeluaran, 0, ',', '.') }}</strong></div>
    </section>
    <section class="panel" aria-label="Riwayat transaksi">
        <div class="toolbar">
            <div><h2>Riwayat transaksi</h2><p class="muted">{{ $transaksi->total() }} transaksi · termasuk transfer@if ($pencarian !== '') · Semua tanggal@endif</p></div>
            <form method="get" action="{{ route('kas.publik', ['token' => $share->public_token]) }}">
                <label>Bulan<input type="month" name="bulan" value="{{ $bulan }}" required></label>
                <label>Cari transaksi (semua tanggal)<input type="search" name="q" value="{{ $pencarian }}" placeholder="Deskripsi, aktivitas, atau jenis" maxlength="200"></label>
                <button type="submit">Tampilkan</button>
                @if ($pencarian !== '')<a href="{{ route('kas.publik', ['token' => $share->public_token, 'bulan' => $bulan]) }}">Hapus pencarian</a>@endif
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Aktivitas / Deskripsi</th><th class="number">Nominal</th></tr></thead>
                <tbody>
                @forelse ($transaksi as $item)
                    <tr>
                        <td class="mobile-cell">
                            <div class="public-mobile-summary" data-public-transaksi-mobile data-tone="{{ \App\Filament\Resources\TransaksiResource::getWarnaTipeTransaksi($item->jenis, $item->tipe_transfer) }}">
                                <div class="public-mobile-main">
                                    <span class="public-mobile-type">{{ $item->jenis }}</span>
                                    <span class="public-mobile-amount">Rp {{ number_format($item->nominal, 0, ',', '.') }}</span>
                                </div>
                                <div class="public-mobile-detail">
                                    <span class="public-mobile-activity">{{ $item->aktivitas ? ucfirst($item->aktivitas) : $item->jenis }}</span>
                                    <span class="public-mobile-date">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</span>
                                </div>
                                @if ($item->deskripsi)<p class="public-mobile-description">{{ $item->deskripsi }}</p>@endif
                            </div>
                        </td>
                        <td class="desktop-cell date">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                        <td class="desktop-cell">{{ $item->jenis }}</td>
                        <td class="desktop-cell">{{ $item->aktivitas ? ucfirst($item->aktivitas) : $item->jenis }}@if ($item->deskripsi)<p class="muted">{{ $item->deskripsi }}</p>@endif</td>
                        <td class="desktop-cell number {{ in_array($item->jenis, ['Pemasukan', 'Transfer Pemasukan']) ? 'incoming' : 'outgoing' }}">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">{{ $pencarian !== '' ? 'Tidak ada transaksi yang cocok.' : 'Belum ada transaksi pada bulan ini.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <nav aria-label="Halaman transaksi">
            @if ($transaksi->previousPageUrl())<a href="{{ $transaksi->previousPageUrl() }}" rel="prev">← Sebelumnya</a>@endif
            <span class="muted">Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}</span>
            @if ($transaksi->nextPageUrl())<a href="{{ $transaksi->nextPageUrl() }}" rel="next">Berikutnya →</a>@endif
        </nav>
    </section>
    <footer class="muted">Akses hanya untuk melihat.
        @if ($share->berlaku_sampai)Link berlaku sebelum {{ $share->berlaku_sampai->format('d M Y') }}.@else Link berlaku sampai pemilik mencabut akses.@endif
    </footer>
</main>
</body>
</html>
