# Panduan Troubleshooting: Notifikasi Telegram Konfirmasi Pembayaran Tidak Muncul di Production

Dokumen ini berisi panduan diagnosis dan langkah penyelesaian masalah ketika notifikasi Telegram untuk konfirmasi pembayaran langganan tidak terkirim di lingkungan production.

---

## Ringkasan Alur Kerja Notifikasi Pembayaran

1. Pengguna mengunggah bukti pembayaran melalui aksi konfirmasi pembayaran di halaman Langganan.
2. Service [`App\Services\KonfirmasiPembayaranLangganan::handle()`](../app/Services/KonfirmasiPembayaranLangganan.php:16) memvalidasi order dan menyimpan bukti ke database.
3. Setelah transaksi database commit ([`DB::afterCommit`](../app/Services/KonfirmasiPembayaranLangganan.php:48)), antrean job dipicu via `dispatch(new KirimNotifTelegramPembayaran($langgananId, false))`.
4. Job [`App\Jobs\KirimNotifTelegramPembayaran`](../app/Jobs/KirimNotifTelegramPembayaran.php:14) yang mengimplementasikan `ShouldQueue` masuk ke antrean database.
5. Worker antrean mengeksekusi job, lalu memanggil [`App\Services\TelegramNotifier::kirimNotifikasiPembayaran()`](../app/Services/TelegramNotifier.php:170).
6. Pesan dikirimkan melalui HTTP POST ke endpoint Bot API Telegram `https://api.telegram.org/bot<TOKEN>/sendMessage`.

---

## Kemungkinan Penyebab dan Solusi

### 1. Queue Worker Tidak Berjalan di Production (Paling Sering Terjadi)

- **Gejala**:
  - Konfirmasi pembayaran berhasil disimpan di aplikasi.
  * Notifikasi database admin Filament muncul.
  * Namun pesan Telegram tidak terkirim sama sekali dan tidak ada log error Telegram di `storage/logs/laravel.log`.
- **Penyebab**:
  - Di production, aplikasi menggunakan driver `QUEUE_CONNECTION=database` (sesuai acuan [`.env.example`](../.env.example:41) dan [`docs/deploy-apku-invishar-com-domainesia.md`](deploy-apku-invishar-com-domainesia.md:129)).
  - Kelas [`App\Jobs\KirimNotifTelegramPembayaran`](../app/Jobs/KirimNotifTelegramPembayaran.php:14) adalah antrean latar belakang (`ShouldQueue`).
  - Bila cron job worker antrean di server cPanel/hosting belum aktif atau mati, job akan tertahan di tabel database `jobs` dan tidak pernah dieksekusi.
- **Cara Diagnosis**:
  1. Periksa antrean melalui perintah Artisan bawaan:
     ```bash
     # Memantau jumlah antrean aktif pada queue
     php artisan queue:monitor default,import-transaksi

     # Menampilkan daftar job yang gagal dieksekusi
     php artisan queue:failed
     ```
  2. Periksa isi dan jumlah baris tabel `jobs` langsung menggunakan Artisan Tinker:
     ```bash
     # Menghitung total job yang masih tertahan di tabel jobs
     php artisan tinker --execute="echo 'Total pending jobs: ' . DB::table('jobs')->count() . PHP_EOL;"

     # Menampilkan rincian data job yang sedang mengantre
     php artisan tinker --execute="DB::table('jobs')->get(['id', 'queue', 'attempts', 'created_at'])->dump();"
     ```
  3. Atau periksa via SQL (phpMyAdmin/terminal MySQL):
     ```sql
     SELECT id, queue, attempts, reserved_at, available_at, created_at FROM jobs;
     ```
     Jika terdapat baris antrean yang tidak kunjung berkurang, worker antrean tidak sedang berjalan.
- **Solusi**:
  1. Jalankan pengujian manual di terminal production:
     ```bash
     php artisan queue:work --stop-when-empty
     ```
     Jika pesan Telegram langsung terkirim setelah perintah ini dijalankan, masalahnya pasti ada pada worker antrean.
  2. Pastikan Cron Job cPanel sudah terkonfigurasi untuk memproses antrean secara berkala (lihat panduan [`docs/deploy-apku-invishar-com-domainesia.md`](deploy-apku-invishar-com-domainesia.md:200)):
     ```bash
     * * * * * /usr/local/bin/php /home/username/apku/artisan queue:work --stop-when-empty --tries=1 --timeout=300 --queue=import-transaksi,default >> /dev/null 2>&1
     ```

---

### 2. Konfigurasi Environment (`.env`) Belum Lengkap atau Salah Nama

- **Gejala**:
  - Di `storage/logs/laravel.log` terdapat entri:
    `Telegram notifikasi pembayaran dinonaktifkan: kredensial tidak lengkap` atau `chat_id_pembayaran kosong`.
- **Penyebab**:
  - Konfigurasi [`config/services.php`](../config/services.php:37) memerlukan:
    - `services.telegram.bot_token` diambil dari env `TELEGRAM_BOT_TOKEN`.
    - `services.telegram.chat_id_pembayaran` diambil dari env `TELEGRAM_PAYMENT_CHAT_ID`.
  - Jika salah satu kosong di file `.env` production, pengiriman dibatalkan secara diam-diam oleh pengecekan guard di [`App\Jobs\KirimNotifTelegramPembayaran::handle()`](../app/Jobs/KirimNotifTelegramPembayaran.php:42) dan [`App\Services\TelegramNotifier::kirimNotifikasiPembayaran()`](../app/Services/TelegramNotifier.php:173).
  - Nama variabel env sering tertukar, misalnya ditulis `TELEGRAM_CHAT_ID_PEMBAYARAN` padahal yang dibaca di config adalah `TELEGRAM_PAYMENT_CHAT_ID`.
- **Solusi**:
  1. Pastikan kedua baris berikut ada di `.env` production:
     ```env
     TELEGRAM_BOT_TOKEN=123456789:AAxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     TELEGRAM_PAYMENT_CHAT_ID=-100xxxxxxxxxx
     ```
  2. Bersihkan dan perbarui cache konfigurasi:
     ```bash
     php artisan config:clear
     php artisan config:cache
     ```

---

### 3. Masalah Hak Akses Bot atau Format Chat ID Telegram

- **Gejala**:
  - Di `storage/logs/laravel.log` terdapat entri:
    `Pengiriman notifikasi ke Telegram gagal.` dengan status HTTP `400` atau `403`.
- **Penyebab**:
  - **Status 403 Forbidden**: Bot belum ditambahkan ke channel/grup tujuan, atau bot belum diberi hak akses sebagai **Administrator** dengan izin kirim pesan (*Post Messages*).
  - **Status 400 Bad Request**: Format `TELEGRAM_PAYMENT_CHAT_ID` salah. Channel atau Supergroup Telegram selalu memiliki ID negatif dengan prefix `-100` (contoh: `-1002468135790`). Jika tanda minus atau angka `-100` terpotong, Telegram menolak request.
- **Solusi**:
  1. Buka channel/grup Telegram tujuan > *Manage Channel* / *Info* > *Administrators*.
  2. Tambahkan bot Anda sebagai Admin dan aktifkan izin *Post Messages*.
  3. Pastikan Chat ID menyertakan prefix `-100` secara lengkap.

---

### 4. Firewall atau Batasan Jaringan Outbound HTTP di Hosting

- **Gejala**:
  - Di `storage/logs/laravel.log` terdapat entri:
    `Pengiriman notifikasi ke Telegram mengalami exception.` dengan pesan timeout atau *cURL error 28: Connection timed out*.
- **Penyebab**:
  - Sebagian shared hosting membatasi koneksi keluar (*outbound traffic*) ke domain pihak ketiga seperti `api.telegram.org`.
  - Timeout bawaan pada [`App\Services\TelegramNotifier::kirim()`](../app/Services/TelegramNotifier.php:46) adalah 10 detik. Jika koneksi lambat atau terhalang firewall hosting, proses akan timeout.
- **Solusi**:
  1. Uji koneksi dari terminal hosting:
     ```bash
     curl -v https://api.telegram.org
     ```
  2. Jika koneksi diblokir, hubungi penyedia hosting untuk membuka akses keluar (*outgoing port 443*) ke `api.telegram.org`.

---

### 5. Format Parse Mode HTML pada Pesan Pembayaran

- **Catatan Implementasi**:
  - Method [`App\Services\TelegramNotifier::buatPesanPembayaran()`](../app/Services/TelegramNotifier.php:192) memformat teks menggunakan tag HTML (`<b>`, `<a href="...">`).
  - Pada [`App\Services\TelegramNotifier::kirimPembayaran()`](../app/Services/TelegramNotifier.php:91), parameter `$parseMode = 'HTML'` perlu dipastikan diteruskan ke method [`App\Services\TelegramNotifier::kirim()`](../app/Services/TelegramNotifier.php:30) agar tag HTML dirender dengan benar dan tidak tampil sebagai teks mentah.

---

## Langkah Cepat Verifikasi di Server Production

Gunakan urutan langkah ini untuk mengisolasi masalah dengan cepat:

```bash
# 1. Pastikan konfigurasi env terbaca
php artisan tinker --execute="echo 'Token: ' . config('services.telegram.bot_token') . PHP_EOL . 'Chat ID: ' . config('services.telegram.chat_id_pembayaran') . PHP_EOL;"

# 2. Uji kirim notifikasi langsung via tinker
php artisan tinker --execute="app(\App\Services\TelegramNotifier::class)->kirimNotifikasiPembayaran('Uji Notifikasi', 'Test pengiriman Telegram production', config('app.url'));"

# 3. Jalankan antrean manual bila pesan uji pada langkah 2 berhasil
php artisan queue:work --stop-when-empty

# 4. Periksa log terbaru jika ada kegagalan
tail -n 50 storage/logs/laravel.log
```
