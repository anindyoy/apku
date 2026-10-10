# Plan: Coba Gratis Premium 30 Hari di APKu

Rencana fitur coba gratis Premium selama sebulan untuk aplikasi APKu, disusun berdasarkan dokumen FITUR\_APLIKASI.md.

## 1. Ringkasan keputusan

| Aspek | Keputusan |
| --- | --- |
| Pemicu | User klik tombol **Mulai coba gratis** (tidak otomatis) |
| Kuota | Sekali per akun, per nomor HP, dan per email |
| Syarat | Email terverifikasi dan nomor HP terisi |
| Durasi | 30 hari tetap (hari ini + 30) |
| Pencatatan | Tabel khusus `trial_premium` |
| Saat berakhir | Mengikuti perilaku Premium kedaluwarsa yang sudah ada (kas/dompet ke-3 dan seterusnya terlihat, tidak bisa diubah) |
| Beli saat trial | Sisa trial ditambahkan ke masa aktif berbayar |
| Komunikasi | Banner status dan email pengingat |
| Pengukuran | Konversi trial ke berbayar tampil di dashboard admin |
| Rilis | Ikut peluncuran pertama |

Asumsi: **"user baru"** berarti akun yang belum pernah punya `masa_aktif` dan belum pernah punya langganan yang disetujui.

## 2. Alur pengguna

1. User Reguler yang memenuhi syarat melihat kartu **Coba Premium gratis 30 hari** di Dashboard dan menu Langganan.
2. Jika email belum terverifikasi atau nomor HP kosong, tombol diganti arahan: "Lengkapi dulu: verifikasi email / isi nomor HP di Akun Saya".
3. Klik tombol, lalu muncul modal konfirmasi yang menyebut manfaat, durasi, tanggal berakhir, dan konsekuensi setelahnya.
4. Trial aktif: akun menjadi Premium, banner "Trial Premium, sisa X hari" muncul, dan email sambutan terkirim.
5. Mendekati akhir: email H-7 dan H-1, banner berubah kuning lalu merah.
6. Berakhir: akun kembali Reguler. Email berisi ajakan berlangganan dan jumlah kas/dompet yang terkunci.
7. User beli paket kapan saja. Persetujuan admin menambah masa aktif dari sisa trial, bukan menimpa.

## 3. Perubahan database

### Tabel baru `trial_premium`

- `id`
- `user_id`: FK users, **null on delete** (agar catatan tetap ada walau akun dihapus, sehingga trial tidak bisa didaur ulang dengan hapus lalu daftar ulang)
- `hp_normal`: unik (hasil normalisasi 08xx / +62 / 62 menjadi satu format)
- `email_normal`: unik (hasil normalisasi, misalnya Gmail mengabaikan titik dan `+alias`)
- `mulai_pada`, `berakhir_pada`: date
- `status`: `aktif` / `berakhir` / `dikonversi`
- `langganan_id`: FK `langganans`, nullable, null on delete
- `dikonversi_pada`: nullable
- `timestamps`

### Pengaturan global

Di `application_settings` (sudah ada): `trial.aktif` (saklar on/off) dan `trial.durasi_hari` (default 30), supaya admin bisa mematikan atau mengubah tanpa deploy.

Tidak perlu mengubah tabel `users`, karena status Premium tetap ditentukan oleh `masa_aktif` yang sudah ada.

## 4. Aturan bisnis

**Syarat kelayakan** (semua harus terpenuhi, dicek di server di dalam satu transaksi dengan kunci baris user):

- role `user`, bukan admin
- email terverifikasi dan nomor HP terisi
- `masa_aktif` kosong dan belum pernah ada langganan disetujui
- belum ada catatan di `trial_premium` untuk `user_id`, `hp_normal`, maupun `email_normal`
- saklar `trial.aktif` menyala

**Aktivasi:** buat catatan trial, set `type` ke premium dan `masa_aktif` ke hari ini + 30, lalu bersihkan cache dashboard pengguna (cache 30 menit) lewat event model.

**Penutupan:** perintah terjadwal harian menandai trial yang lewat `berakhir_pada` menjadi `berakhir` dan mengirim email penutup. Tidak ada data yang dihapus.

**Konversi:** saat admin menyetujui langganan milik user yang punya trial, status menjadi `dikonversi`, lalu `langganan_id` dan `dikonversi_pada` diisi. Masa aktif baru dihitung dari sisa yang ada (perilaku ini sudah ada).

## 5. Titik yang rawan

- **Pengingat H-30 yang sudah ada** akan langsung terkirim ke user trial karena masa aktifnya hanya 30 hari. Pengingat itu harus dilewati untuk user dengan trial aktif, dan diganti pengingat khusus trial (H-7, H-1).
- **Nomor HP belum diverifikasi OTP**, jadi orang bisa mengisi nomor asal. Untuk peluncuran pertama ini cukup; jika ada penyalahgunaan, tahap berikutnya adalah verifikasi OTP.
- **Normalisasi email** hanya efektif untuk penyedia yang aliasnya dikenal (Gmail). Domain lain tidak bisa dijamin.
- **Zona waktu:** hitung tanggal berdasarkan Asia/Jakarta agar trial tidak berakhir sehari lebih cepat.
- **Kas/dompet yang dibuat saat trial** akan terkunci setelah trial. Tampilkan peringatan ini di modal konfirmasi dan email penutup supaya tidak mengejutkan user.

## 6. Perubahan sisi antarmuka

### User

- Kartu CTA di Dashboard dan halaman Langganan (hanya untuk yang layak atau hampir layak).
- Banner status trial di Dashboard, plus badge di navbar.
- Halaman Akun Saya menampilkan status trial dan tanggal berakhir.
- Landing page: sebutkan "Coba Premium gratis 30 hari" dan tambahkan di tabel perbandingan.
- Halaman Tutorial (`resources/content/tutorial.json`): tambah topik trial.

### Admin

- Dashboard admin: jumlah trial aktif, trial berakhir minggu ini, total trial, jumlah konversi, dan persentase konversi.
- Resource baca-saja **Trial Premium** (user, tanggal, status, konversi), dengan opsi membatalkan trial jika perlu (misalnya akun mencurigakan).
- Pengaturan: saklar dan durasi trial.

## 7. Notifikasi

| Waktu | Kanal | Isi |
| --- | --- | --- |
| Hari 0 | Email + dalam aplikasi | Selamat datang, daftar fitur, tanggal berakhir |
| H-7 | Email + banner kuning | Pengingat dan ajakan paket |
| H-1 | Email + banner merah | Peringatan terakhir, jumlah kas/dompet yang akan terkunci |
| Berakhir | Email + dalam aplikasi | Kembali ke Reguler, ajakan berlangganan (voucher opsional) |

## 8. Pengujian (Pest)

- Kelayakan: tiap syarat gagal menolak dengan pesan yang sesuai (email belum verifikasi, HP kosong, sudah pernah, admin, saklar mati).
- Pencegahan ganda: HP sama atau email ternormalisasi sama ditolak; klik dua kali cepat atau permintaan paralel hanya membuat satu trial.
- Aktivasi: `masa_aktif` benar, fitur Premium terbuka (kas ke-3, import, laporan, kolaborasi).
- Penutupan: trial lewat tanggal berubah ke `berakhir`, akses kembali Reguler, data tetap utuh.
- Konversi: beli paket saat trial, masa aktif = sisa trial + durasi paket, status `dikonversi`.
- Pengingat: user trial tidak menerima H-30 bawaan, hanya H-7 dan H-1.
- Akun yang dihapus tetap memblokir pendaftaran ulang dengan HP/email yang sama.
- Dashboard admin: angka statistik benar.

## 9. Tahapan pengerjaan

1. **Fondasi:** migration `trial_premium`, model, helper normalisasi HP/email, pengaturan global, service aktivasi, dan tes kelayakan.
2. **Siklus hidup:** perintah penutupan terjadwal, hook konversi di persetujuan langganan, penyesuaian pengingat H-30.
3. **Antarmuka user:** kartu CTA, modal konfirmasi, banner, badge, Akun Saya.
4. **Notifikasi:** keempat email dan notifikasi dalam aplikasi.
5. **Admin:** statistik, resource Trial Premium, pengaturan.
6. **Konten dan dokumen:** landing page, tutorial, serta pembaruan `FITUR_APLIKASI.md` (bagian langganan dan skema database), sesuai catatan di dokumen bahwa skema harus diperbarui setiap ada migrasi.
7. **Uji menyeluruh** lalu rilis bersama peluncuran pertama.
