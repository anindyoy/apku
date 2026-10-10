<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Invoice {{ $order->kode_order }} · APKu</title>
    <link rel="icon" href="{{ asset('logo-options/apku-dompet.svg') }}" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3" data-invoice-toolbar>
            <a href="{{ url('/admin/langganans') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke langganan</a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Cetak / Simpan PDF</button>
        </div>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-invoice>
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-teal-950 px-6 py-6 text-white sm:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-teal-200">APKu · Invoice langganan</p>
                    <h1 class="mt-2 text-2xl font-bold" data-invoice-code>{{ $order->kode_order }}</h1>
                    <p class="mt-1 text-sm text-teal-100">Tanggal order: {{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
                <img src="{{ asset('logo-options/apku-dompet.svg') }}" alt="APKu" class="h-12 w-12 shrink-0">
            </header>

            <div class="grid gap-6 px-6 py-6 sm:grid-cols-2 sm:px-8">
                <section aria-label="Pelanggan">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Ditagihkan kepada</h2>
                    <p class="mt-2 font-semibold">{{ $order->user?->name ?? '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order->user?->email ?? '-' }}</p>
                    <p class="mt-3 text-sm text-slate-600">Status: <span class="font-semibold text-slate-900">{{ $order->status->label() }}</span></p>
                    @if ($order->masa_aktif_sampai)
                        <p class="text-sm text-slate-600">Aktif sampai: {{ $order->masa_aktif_sampai->format('d M Y') }}</p>
                    @endif
                </section>
                <section aria-label="Pembayaran">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Tujuan pembayaran</h2>
                    <p class="mt-2 font-semibold">{{ $order->label_metode_pembayaran }}</p>
                    @php($detail = $order->detail_pembayaran ?? [])
                    @if (filled(data_get($detail, 'nama_penyedia')))
                        <p class="text-sm text-slate-600">{{ data_get($detail, 'nama_penyedia') }}</p>
                    @endif
                    @if (filled(data_get($detail, 'nomor_tujuan')))
                        {{-- Sorot nomor tujuan agar mudah dibaca dan disalin sebelum membayar. --}}
                        @php($nomorTujuan = (string) data_get($detail, 'nomor_tujuan'))
                        <div class="mt-3 rounded-xl border border-teal-200 bg-teal-50 p-3" data-invoice-account-box>
                            <p class="text-xs font-bold uppercase tracking-wider text-teal-800">Nomor tujuan</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="text-xl font-extrabold tracking-wide text-slate-900 select-all sm:text-2xl" data-invoice-account>{{ $nomorTujuan }}</span>
                                <button type="button" data-copy-account-button="{{ $nomorTujuan }}" class="rounded-lg bg-teal-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1">Salin</button>
                            </div>
                            <p class="mt-1 hidden text-xs font-semibold text-teal-700" data-copy-feedback>Nomor tersalin.</p>
                        </div>
                    @endif
                    @if (filled(data_get($detail, 'nama_pemilik')))
                        <p class="text-sm text-slate-600">Atas nama: {{ data_get($detail, 'nama_pemilik') }}</p>
                    @endif
                    @if (filled(data_get($detail, 'instruksi')))
                        <p class="mt-2 text-sm text-slate-600">{{ data_get($detail, 'instruksi') }}</p>
                    @endif
                </section>
            </div>

            <div class="px-6 sm:px-8">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <th scope="col" class="px-4 py-3">Paket</th>
                                <th scope="col" class="px-4 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-slate-200">
                                <td class="px-4 py-3">
                                    <span class="font-semibold">{{ $order->label_paket }}</span>
                                    <span class="block text-xs text-slate-500">{{ $order->durasi_hari }} hari</span>
                                </td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($order->harga, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t border-slate-200">
                                <td class="px-4 py-3">
                                    Diskon
                                    @if (filled($order->kode_voucher))
                                        <span class="block text-xs text-slate-500">Voucher {{ $order->kode_voucher }} · {{ $order->persentase_diskon }}%</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">− Rp {{ number_format($order->nominal_diskon, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t border-slate-200 bg-amber-50">
                                <td class="px-4 py-3 font-bold">Total pembayaran</td>
                                <td class="px-4 py-3 text-right font-bold" data-invoice-total>Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if (filled($order->catatan_admin))
                    <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Catatan admin: {{ $order->catatan_admin }}</p>
                @endif
                <p class="mt-4 text-xs leading-relaxed text-slate-500">Simpan invoice ini sebagai bukti order. Lakukan pembayaran sesuai total, lalu gunakan Konfirmasi pembayaran pada halaman langganan dan unggah bukti JPG, JPEG, PNG, atau PDF maksimal 3 MB. Aktivasi Premium dilakukan setelah pembayaran disetujui admin.</p>
            </div>

            <footer class="mt-6 border-t border-slate-200 px-6 py-4 text-right text-xs text-slate-400 sm:px-8">Dibuat pada {{ now()->locale('id')->translatedFormat('d F Y, H:i') }}</footer>
        </article>
    </main>
    <script type="module" src="{{ asset('js/invoice-copy.js') }}"></script>
</body>
</html>
