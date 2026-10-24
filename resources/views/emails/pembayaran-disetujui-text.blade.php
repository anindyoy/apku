Halo, {{ $user->name ?? 'Pengguna APKu' }}!

Kabar baik! Pembayaran untuk order langganan #{{ $order->kode_order }} telah disetujui oleh admin. Akun Premium Anda kini telah aktif.

DETAIL LANGGANAN:
- Kode Order: {{ $order->kode_order }}
- Paket: {{ $order->label_paket }}
- Masa Aktif Mulai: {{ $order->masa_aktif_mulai?->translatedFormat('d F Y') ?? '-' }}
- Masa Aktif Sampai: {{ $order->masa_aktif_sampai?->translatedFormat('d F Y') ?? '-' }}
- Total Dibayar: Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}

Kini Anda dapat menikmati fitur Premium APKu: menambah kas dan sumber dana tanpa batas kuota, kolaborasi kas bersama, serta fitur pendukung lainnya.

Buka Dashboard APKu melalui tautan berikut:
{{ $url }}

---
APKu — Catat uang, pahami keuangan
{{ config('app.url') }}
