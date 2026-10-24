# Plan: Notifikasi Email Pembayaran Langganan

## 1. Tujuan

Mengirim email otomatis pada alur pembayaran langganan Premium:

1. **Invoice** dikirim ke email user saat user non-admin selesai membuat (submit) order langganan.
2. **Pembayaran disetujui** dikirim ke user saat admin menerima konfirmasi pembayaran.
3. **Pembayaran ditolak** dikirim ke user saat admin menolak konfirmasi pembayaran, beserta alasan penolakan.

## 2. Keputusan yang Sudah Disepakati

| Topik | Keputusan |
| --- | --- |
| Isi email invoice | Invoice lengkap di badan email, bisa dibaca tanpa login |
| Cara kirim | Lewat antrean (queue), tidak menghambat request |
| Saat gagal kirim | Retry otomatis; jika tetap gagal, kirim notifikasi Telegram |

## 3. Cakupan

**Termasuk**
- Tiga email di atas (HTML responsif + versi teks polos).
- Antrean khusus email, retry/backoff, dan notifikasi Telegram saat gagal permanen.
- Test terprogram, pembaruan `FITUR_APLIKASI.md` dan `resources/content/tutorial.json`.

**Tidak termasuk**
- Email ke admin saat user mengirim bukti pembayaran.
- Lampiran PDF invoice.
- Perubahan skema database (tidak dibutuhkan; data diambil dari kolom snapshot di tabel `langganans`).

## 4. Hal yang Perlu Diperiksa Sebelum Implementasi

Source code adalah sumber kebenaran, jadi cek hal berikut dulu:

- Lokasi proses pembuatan order dan proses setujui/tolak (Action/Service/Livewire/Filament). Pasang pemicu di satu titik pusat, bukan di tiap UI.
- Class notifikasi dalam aplikasi untuk "disetujui/ditolak" yang sudah ada. Jika sudah memakai Notification Laravel, **tambahkan channel `mail`** ke class tersebut agar tidak ada notifikasi ganda.
- Pola email yang sudah ada (verifikasi akun, reset password, trial H-7/H-1) untuk dipakai ulang sebagai layout: logo APKu, aksen teal, sapaan nama, tombol aksi, teks polos.
- Halaman invoice yang ada, untuk menyamakan isi dan format angka (rupiah tanpa desimal, separator titik).
- Pengirim Telegram yang sudah dipakai untuk exception production dan `harga-emas:pantau`; pakai ulang, jangan buat klien baru.
- Konfigurasi worker production (`QUEUE_CONNECTION`, supervisor/proses worker) dan SMTP/mailer.

## 5. Rancangan

### 5.1 Pemicu

| Peristiwa | Pemicu | Syarat |
| --- | --- | --- |
| Order dibuat | Setelah order langganan tersimpan | Pemilik order bukan admin dan email terisi |
| Pembayaran disetujui | Setelah status berubah menjadi disetujui | Hanya pada perpindahan status dari "menunggu verifikasi", bukan setiap simpan ulang |
| Pembayaran ditolak | Setelah status berubah menjadi ditolak | Sama seperti di atas; `catatan_admin` (alasan) wajib terisi |

- Semua kirim dijalankan **setelah transaksi database commit** (`afterCommit`), agar email tidak terkirim untuk order yang di-rollback dan job tidak berjalan sebelum data ada.
- Penerima: `users.email` pemilik order.

### 5.2 Class

- `InvoiceOrderNotification` (mail)
- `PembayaranDisetujuiNotification` (mail + database, melanjutkan yang sudah ada jika ada)
- `PembayaranDitolakNotification` (mail + database, melanjutkan yang sudah ada jika ada)

Semua `implements ShouldQueue`, `use Queueable`, `afterCommit = true`. Nama class final mengikuti konvensi penamaan proyek.

### 5.3 Konten email

**Invoice (saat submit order)**
- Sapaan nama user, kode order, tanggal order.
- Rincian: paket, harga, voucher/kode voucher (jika ada), diskon, **total pembayaran**.
- Metode pembayaran beserta detail tujuan (nama penyedia, nomor tujuan, nama pemilik) dan instruksi.
- Langkah berikutnya: lakukan pembayaran lalu kirim bukti di halaman Langganan; tombol aksi ke halaman Langganan (login diperlukan hanya untuk mengirim bukti).
- Data diambil dari kolom snapshot di `langganans` (`label_paket`, `harga`, `total_pembayaran`, `label_metode_pembayaran`, `detail_pembayaran`, dst.) agar identik dengan invoice walau paket/metode kemudian berubah.
- Tidak menyertakan bukti pembayaran atau data sensitif lain.

**Pembayaran disetujui**
- Kode order, paket, masa aktif mulai dan sampai, total dibayar, tombol ke Dashboard.

**Pembayaran ditolak**
- Kode order, alasan penolakan (`catatan_admin`), langkah lanjutan (kirim ulang bukti atau buat order baru), dan tombol bantuan WhatsApp admin dari setting `kontak` bila nomor terisi.

**Template**
- Gunakan layout email yang sama dengan email verifikasi akun (HTML responsif + teks polos).
- Teks yang terlihat user memakai istilah resmi proyek; hindari kata "dompet" dan "audit" pada teks tampilan.
- Email adalah pengecualian styling: CSS inline pada template email diperbolehkan karena klien email tidak mendukung Tailwind/Flowbite.

### 5.4 Antrean, retry, dan kegagalan

- Gunakan queue khusus (mis. `email`) dan pastikan worker production memprosesnya, termasuk di prioritas `--queue`.
- `tries = 3` dengan `backoff` bertahap (mis. 60, 300, 900 detik).
- Pastikan `retry_after` konfigurasi antrean lebih panjang dari timeout job.
- Setelah percobaan habis (`failed()`): kirim pesan Telegram berisi jenis email, kode order, dan pesan error ringkas. **Jangan** menyertakan isi email atau data pembayaran.
- Kegagalan email tidak boleh menggagalkan atau membatalkan order maupun verifikasi pembayaran.
- Hindari duplikat: kirim hanya pada perpindahan status, dan tangani job yang dijalankan ulang agar tidak mengirim dua kali untuk peristiwa yang sama.

## 6. Langkah Implementasi

1. Telusuri titik pembuatan order dan aksi setujui/tolak; catat notifikasi dalam aplikasi yang sudah ada.
2. Buat tiga class notifikasi dengan konfigurasi antrean, `afterCommit`, retry/backoff, dan `failed()` ke Telegram.
3. Buat template email (HTML + teks polos) berbasis layout email yang sudah ada.
4. Pasang pemicu pada titik pusat (order dibuat, disetujui, ditolak) dengan guard non-admin dan perpindahan status.
5. Atur queue `email` di worker dan dokumentasi environment; uji lokal dengan Mailpit di Laravel Herd.
6. Tulis test (lihat bagian 7).
7. Perbarui dokumentasi (lihat bagian 8).
8. Telusuri istilah terlarang (`dompet`, `audit`) pada teks tampilan yang disentuh.

## 7. Rencana Test

Semua perintah lokal wajib memakai `--filter` dan `--parallel`, misalnya:

```bash
php artisan test --filter="EmailPembayaran" --parallel
```

Kasus uji (gunakan `Notification::fake()` / `Queue::fake()`, hindari `assertSee`; periksa isi lewat properti/data mail atau `render()` lalu assert string):

- Order dibuat oleh user non-admin menempatkan `InvoiceOrderNotification` ke antrean untuk user pemilik; tidak terkirim untuk admin.
- Invoice memuat kode order, total, dan detail pembayaran dari kolom snapshot.
- Setujui pembayaran mengantre `PembayaranDisetujuiNotification` dengan masa aktif yang benar; setujui ulang tidak mengirim email kedua.
- Tolak pembayaran mengantre `PembayaranDitolakNotification` dengan alasan penolakan.
- Notifikasi memakai queue dan `afterCommit` yang diharapkan, serta `tries` dan `backoff` sesuai rencana.
- `failed()` memanggil pengirim Telegram (gunakan `Http::fake()`), dan tidak membocorkan isi email.
- Email gagal terkirim tidak membatalkan order atau verifikasi pembayaran.

Seeder dikecualikan dari testing. Full test suite dijalankan di GitHub Actions, bukan lokal.

## 8. Pembaruan Dokumentasi (dalam pekerjaan yang sama)

- `FITUR_APLIKASI.md`
  - Bagian 12/13 (Langganan premium): tambahkan email invoice, email pembayaran disetujui, dan email pembayaran ditolak, termasuk antrean, retry, dan notifikasi Telegram saat gagal.
  - Bagian Skema database: tidak berubah karena tidak ada migrasi baru; pastikan tidak ada klaim yang tidak sesuai.
- `resources/content/tutorial.json`
  - Perbarui topik langganan/pembayaran: user akan menerima invoice lewat email setelah order, serta email hasil verifikasi (disetujui/ditolak), dan anjuran memeriksa folder spam. Pertahankan struktur JSON; jalankan test tutorial yang terdampak dengan `--filter` dan `--parallel`.

## 9. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Worker antrean production tidak memproses queue `email` | Daftarkan queue di perintah worker dan verifikasi saat rilis |
| Email dobel pada retry atau simpan ulang | Kirim hanya pada perpindahan status; satu pemicu pusat |
| Email masuk spam | Pastikan SPF/DKIM/DMARC domain pengirim dan alamat `From` konsisten |
| Kegagalan SMTP beruntun membanjiri Telegram | Telegram hanya setelah retry habis, satu pesan per job gagal |
| Nomor rekening tujuan terkirim ke alamat salah | Hanya kirim ke `users.email` terverifikasi milik pemilik order |

## 10. Kriteria Selesai

- Ketiga email terkirim lewat antrean pada alur yang benar dan tidak terkirim untuk admin.
- Kegagalan email tidak mengganggu order atau verifikasi, dan memicu Telegram setelah retry habis.
- Test terfilter lulus; `FITUR_APLIKASI.md` dan `tutorial.json` sudah diperbarui.
- Tidak ada `git commit` kecuali diminta secara eksplisit.
