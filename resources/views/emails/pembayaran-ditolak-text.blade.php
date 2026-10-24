Halo, {{ $user->name ?? 'Pengguna APKu' }}!

Mohon maaf, bukti pembayaran yang Anda kirimkan untuk order #{{ $order->kode_order }} belum dapat kami setujui.

ALASAN PENOLAKAN DARI ADMIN:
{{ $order->catatan_admin ?: 'Bukti pembayaran tidak sesuai atau tidak terbaca.' }}

RINGKASAN ORDER:
- Kode Order: {{ $order->kode_order }}
- Paket: {{ $order->label_paket }}
- Total Tagihan: Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}

LANGKAH SELANJUTNYA:
Anda dapat mengunggah ulang bukti pembayaran yang benar melalui halaman Langganan APKu:
{{ $url }}
@if (filled($urlWa))

BANTUAN ADMIN:
Jika ada pertanyaan atau kendala mengenai pembayaran, Anda dapat menghubungi admin via WhatsApp:
{{ $urlWa }}
@endif

---
APKu — Catat uang, pahami keuangan
{{ config('app.url') }}
