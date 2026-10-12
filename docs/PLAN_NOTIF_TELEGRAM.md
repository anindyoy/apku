# Plan: Notifikasi Telegram (Bukti Pembayaran + Exception Laravel)

## 1. Tujuan

- Admin mendapat pesan di channel Telegram khusus setiap kali user mengirim bukti pembayaran langganan (order menunggu verifikasi), sehingga order bisa segera diverifikasi.
- Exception Laravel di production tetap dilaporkan ke Telegram, dengan konfigurasi dan klien Telegram yang dipakai bersama fitur notifikasi pembayaran agar tidak duplikat.

## 2. Keputusan

- Tujuan notifikasi pembayaran: channel Telegram khusus pembayaran.
- Pemicu notifikasi pembayaran: hanya saat user mengirim bukti pembayaran. Pembuatan order, persetujuan, dan penolakan tidak memicu Telegram.
- Isi pesan pembayaran: informasi order + tautan ke halaman admin. Tidak ada tombol Setujui/Tolak di Telegram; verifikasi tetap di aplikasi.
- Notifikasi exception: tetap aktif hanya di production dan hanya jika kredensial bot dan chat tersedia.
- Tidak ada perubahan skema database.

## 3. Asumsi (koreksi bila tidak sesuai)

- Bot Telegram yang sudah dipakai untuk notifikasi exception dan pemantauan harga emas dipakai ulang; hanya `chat_id` channel pembayaran yang baru.
- Notifikasi exception dan notifikasi pembayaran memakai chat/channel terpisah agar pesan error tidak bercampur dengan info order.
- Bot sudah menjadi admin channel pembayaran dengan izin mengirim pesan.
- Jika bukti dikirim ulang pada order yang sama, notifikasi tetap dikirim dengan label "Bukti ulang".
- Bukti pembayaran (file) tidak dilampirkan ke Telegram karena disimpan privat.

## 4. Rancangan

### 4.1 Konfigurasi bersama

- Satu blok konfigurasi Telegram di `config/services.php`:
  - token bot (yang sudah ada),
  - `chat_id` exception (yang sudah ada),
  - `chat_id_pembayaran` (baru, dari env `TELEGRAM_PAYMENT_CHAT_ID`).
- Tambahkan env baru ke `.env.example`.
- Setiap fitur dilewati diam-diam bila token atau chat ID-nya kosong.

### 4.2 Klien Telegram bersama

- Tinjau implementasi Telegram yang sudah dipakai untuk exception dan command `harga-emas:pantau`.
- Jika kode pengirimannya tersebar, satukan menjadi satu service kecil (mis. `TelegramNotifier`) dengan method kirim pesan ke chat ID tertentu, pakai HTTP client Laravel, timeout pendek, dan escape HTML.
- Perubahan refactor tidak boleh mengubah perilaku notifikasi exception dan pemantauan harga emas yang sudah ada.

### 4.3 Notifikasi exception Laravel

- Pastikan exception yang dilaporkan di production tetap terkirim ke chat exception lewat klien bersama.
- Isi pesan: nama aplikasi/environment, kelas exception, pesan singkat (dipotong), file dan baris, URL/route bila ada, waktu. Tidak menyertakan data sensitif (password, token, isi request, bukti pembayaran).
- Batas panjang pesan Telegram (4096 karakter): potong pesan yang terlalu panjang.
- Kegagalan pengiriman exception ke Telegram tidak boleh melempar exception baru; cukup ditulis ke log.
- Hindari banjir pesan: abaikan exception yang memang tidak perlu dilaporkan (validasi, 404, autentikasi) mengikuti daftar `dontReport` Laravel, dan pertimbangkan throttle untuk exception identik dalam jangka pendek (mis. cache beberapa menit).
- Hanya aktif di production; di local/testing tidak mengirim.

### 4.4 Notifikasi bukti pembayaran

**Titik pemicu**

- Cari aksi/service yang menyimpan bukti pembayaran (mengisi `bukti_pembayaran_path` dan `tanggal_konfirmasi` pada `langganans`).
- Setelah data tersimpan dan transaksi database commit, dispatch job dengan `afterCommit`. Gunakan satu titik saja agar tidak terkirim ganda.

**Pengiriman**

- Job antrean khusus (mis. `KirimNotifTelegramPembayaran`), `tries` dan `backoff` terbatas.
- Kegagalan Telegram tidak boleh menggagalkan proses user mengirim bukti; dicatat ke log.
- Hindari loop: kegagalan job ini tidak dilaporkan lagi lewat notifikasi exception Telegram.

**Isi pesan** (parse mode HTML, nilai dinamis di-escape)

- Judul: Bukti pembayaran baru (atau Bukti ulang)
- Kode order, nama user, paket, metode pembayaran, total pembayaran, waktu konfirmasi, kode voucher bila ada
- Tautan ke halaman admin untuk memverifikasi order
- Tidak menyertakan email, nomor HP, atau tautan langsung ke berkas bukti

**Tautan admin**

- Pakai route halaman admin yang menampilkan order menunggu verifikasi (cek route yang berlaku di Filament). Prioritas: tautan langsung ke order; cadangan: daftar order dengan filter status menunggu verifikasi.

## 5. Pengujian

Semua dijalankan dengan `--filter` dan `--parallel`, mis. `php artisan test --filter="NotifTelegramTest" --parallel`. Hindari `assertSee`.

Pembayaran:

- Mengirim bukti memasukkan job ke antrean (`Queue::fake`).
- Job memanggil API Telegram dengan chat ID channel pembayaran (`Http::fake`) dan payload berisi kode order serta tautan admin.
- Tidak mengirim apa pun saat token atau chat ID kosong.
- Order dibuat, disetujui, atau ditolak tidak memicu pengiriman.
- Telegram gagal tidak membatalkan penyimpanan bukti.
- Bukti dikirim ulang memakai label "Bukti ulang".

Exception:

- Exception yang dilaporkan di production memanggil API Telegram dengan chat ID exception (`Http::fake`).
- Tidak mengirim di environment selain production atau saat kredensial kosong.
- Pesan dipotong sesuai batas panjang dan tidak memuat data sensitif.
- Kegagalan pengiriman tidak memunculkan exception baru.
- Exception yang masuk daftar tidak dilaporkan tidak terkirim; exception identik beruntun tidak membanjiri (bila throttle diterapkan).
- Refactor klien bersama tidak mengubah perilaku notifikasi `harga-emas:pantau`.

## 6. Dokumentasi

- `FITUR_APLIKASI.md`: perbarui bagian Fitur pendukung (Telegram) agar mencakup channel exception dan channel pembayaran, serta bagian Langganan bahwa pengiriman bukti memicu notifikasi ke channel Telegram admin.
- `tutorial.json`: tidak perlu diubah (fitur hanya untuk admin, tidak ada perubahan alur user).
- Skema database: tidak berubah.
- Komentar kode ditulis dalam Bahasa Indonesia.

## 7. Langkah kerja

1. Tinjau implementasi Telegram yang sudah ada (exception, `harga-emas:pantau`) dan kode pengiriman bukti.
2. Tambah konfigurasi dan env.
3. Satukan klien Telegram bila masih tersebar, lalu sesuaikan notifikasi exception dan pemantauan harga emas agar memakainya.
4. Buat job dan format pesan pembayaran, lalu pasang dispatch pada titik pengiriman bukti.
5. Tulis dan jalankan test terfilter untuk pembayaran dan exception.
6. Perbarui `FITUR_APLIKASI.md`.
7. (Opsional) Buat task di list ClickUp APKu (ID `901604870720`) dengan prioritas Normal.

## 8. Risiko

- Refactor klien bersama bisa merusak notifikasi exception/harga emas yang sudah jalan: tutup dengan test regresi sebelum dan sesudah refactor.
- Notifikasi pembayaran ganda jika ada lebih dari satu jalur penyimpanan bukti: pastikan hanya satu titik dispatch.
- Banjir pesan exception saat terjadi error berulang: batasi dengan throttle.
- Kebocoran data sensitif lewat pesan exception: jangan sertakan isi request atau data pengguna.
- Antrean tidak berjalan di production: pastikan worker menangani queue default atau tentukan queue khusus.
- Chat ID channel salah (biasanya diawali `-100`): verifikasi dengan pesan uji sebelum rilis.
