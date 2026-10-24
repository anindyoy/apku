Halo, {{ $user->name ?? 'Pengguna APKu' }}!

Terima kasih telah memesan paket langganan di APKu. Berikut adalah rincian tagihan pembayaran Anda:

RINCIAN TAGIHAN:
- Kode Order: {{ $order->kode_order }}
- Tanggal Order: {{ $order->created_at?->format('d M Y H:i') ?? '-' }}
- Paket: {{ $order->label_paket }} ({{ $order->durasi_hari }} hari)
- Harga: Rp {{ number_format($order->harga, 0, ',', '.') }}
@if (filled($order->kode_voucher))
- Voucher: {{ $order->kode_voucher }} (Diskon {{ $order->persentase_diskon }}%: − Rp {{ number_format($order->nominal_diskon, 0, ',', '.') }})
@endif
- Total Tagihan: Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}

TUJUAN PEMBAYARAN:
@php($detail = $order->detail_pembayaran ?? [])
- Metode: {{ $order->label_metode_pembayaran }}
@if (filled(data_get($detail, 'nama_penyedia')))
- Penyedia: {{ data_get($detail, 'nama_penyedia') }}
@endif
@if (filled(data_get($detail, 'nomor_tujuan')))
- Nomor Tujuan / Rekening: {{ data_get($detail, 'nomor_tujuan') }}
@endif
@if (filled(data_get($detail, 'nama_pemilik')))
- Atas Nama: {{ data_get($detail, 'nama_pemilik') }}
@endif
@if (filled(data_get($detail, 'instruksi')))
- Instruksi: {{ data_get($detail, 'instruksi') }}
@endif

LANGKAH SELANJUTNYA:
Silakan lakukan transfer sesuai total tagihan di atas. Setelah transfer berhasil, unggah bukti pembayaran melalui tautan berikut:
{{ $url }}

Catatan:
Pastikan nominal yang Anda transfer tepat hingga digit terakhir agar verifikasi berjalan otomatis dan cepat. Periksa folder Spam jika email ini tidak muncul di kotak masuk utama.

---
APKu — Catat uang, pahami keuangan
{{ config('app.url') }}
