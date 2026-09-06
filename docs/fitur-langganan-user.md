# Rencana Fitur Langganan User

## Tujuan

Menyediakan alur langganan manual dari pemilihan paket sampai aktivasi premium:

1. Admin mengelola opsi langganan yang berisi label, harga, dan durasi.
2. User memilih paket dan membuat order.
3. User mengirim konfirmasi pembayaran beserta bukti pembayaran.
4. Admin memeriksa transaksi lalu menyetujui atau menolak pembayaran.
5. Persetujuan admin mengaktifkan atau memperpanjang masa aktif premium user.
6. User dan admin dapat melihat riwayat langganan sesuai hak aksesnya.

Pembayaran otomatis melalui payment gateway belum termasuk pada tahap ini. Metode pembayaran menggunakan transfer manual dan verifikasi admin.

## Keputusan desain utama

- Nilai uang disimpan sebagai bilangan bulat rupiah agar tidak terkena masalah pembulatan.
- Harga paket minimal Rp1.
- Durasi paket disimpan sebagai jumlah hari. Tampilan dapat mengubahnya menjadi label yang mudah dibaca, misalnya `30 hari` atau `365 hari`.
- Order menyimpan salinan label, harga, dan durasi paket saat order dibuat. Perubahan paket di kemudian hari tidak mengubah riwayat transaksi lama.
- Bukti pembayaran disimpan pada storage privat dan hanya dapat dibuka oleh pemilik order serta admin.
- Aktivasi premium hanya terjadi ketika admin menyetujui pembayaran, bukan ketika user mengunggah bukti.
- Perpanjangan dihitung dari tanggal yang paling akhir antara hari persetujuan dan masa aktif user saat ini. Dengan demikian, sisa masa aktif tidak hangus saat user memperpanjang lebih awal.
- Proses persetujuan harus atomik dan idempoten agar klik atau request ganda tidak menambah masa aktif dua kali.
- Kolom `users.type` dan `users.masa_aktif` tetap digunakan karena aturan akses premium yang ada membaca kedua kolom tersebut.
- User boleh mempunyai beberapa order dengan status aktif secara bersamaan. Setiap order tetap diproses dan diaudit secara independen.
- Order tidak kedaluwarsa secara otomatis.
- Paket tidak dihapus dari aplikasi; paket yang tidak lagi dijual dinonaktifkan untuk menjaga konsistensi audit.
- Persetujuan admin tidak dapat dibatalkan melalui alur biasa. Koreksi dilakukan melalui prosedur admin terpisah yang wajib tercatat.

## Model data

### Tabel `paket_langganans`

- `id`
- `label`: nama paket yang dilihat user
- `harga`: nominal rupiah dalam integer/big integer tanpa nilai negatif
- `durasi_hari`: integer positif
- `is_active`: menentukan apakah paket dapat dipesan user
- `created_at`, `updated_at`

Walaupun kebutuhan inti hanya label, harga, dan durasi, `is_active` diperlukan agar paket lama dapat dihentikan tanpa menghapusnya dan merusak relasi riwayat.

### Tabel `langganans`

- `id`
- `kode_order`: kode unik yang mudah disebutkan user/admin
- `user_id`
- `paket_langganan_id`, nullable agar histori tetap tersedia bila paket dihapus
- `label_paket`: snapshot label saat order
- `harga`: snapshot harga saat order
- `durasi_hari`: snapshot durasi saat order
- `status`
- `bukti_pembayaran_path`, nullable
- `tanggal_konfirmasi`, nullable
- `catatan_user`, nullable
- `catatan_admin`, nullable
- `diverifikasi_oleh`, nullable, relasi ke user admin
- `tanggal_verifikasi`, nullable
- `masa_aktif_mulai`, nullable
- `masa_aktif_sampai`, nullable
- `created_at`, `updated_at`

Index minimum:

- unique pada `kode_order`
- index pada `user_id`, `status`, dan `created_at`
- index pada `diverifikasi_oleh`

Relasi model:

- `PaketLangganan hasMany Langganan`
- `User hasMany Langganan`
- `Langganan belongsTo User`
- `Langganan belongsTo PaketLangganan`
- `Langganan belongsTo User` melalui `diverifikasi_oleh`

### Tabel `metode_pembayarans`

- `id`
- `label`: nama yang dilihat user, misalnya `Transfer Bank BCA`
- `jenis`: bank, dompet digital, QR, atau metode manual lainnya
- `nama_penyedia`: nama bank atau penyedia pembayaran
- `nomor_tujuan`, nullable untuk metode yang memakai nomor rekening/akun
- `nama_pemilik`, nullable
- `instruksi`, nullable
- `gambar_qr_path`, nullable dan disimpan privat
- `is_active`: menentukan apakah metode dapat dipilih pada order baru
- `urutan`: urutan tampilan kepada user
- `created_at`, `updated_at`

Order menyimpan `metode_pembayaran_id` dan snapshot label serta detail tujuan pembayaran yang relevan. Dengan demikian, perubahan rekening tidak mengubah instruksi yang berlaku pada order lama. Admin mengelola metode pembayaran melalui resource khusus admin; metode yang pernah dipakai dinonaktifkan, bukan dihapus.

## Status dan transisi order

| Status | Makna | Aksi berikutnya |
| --- | --- | --- |
| `menunggu_pembayaran` | Order telah dibuat, bukti belum dikirim | User mengirim konfirmasi pembayaran atau membatalkan |
| `menunggu_verifikasi` | Bukti pembayaran sudah dikirim | Admin menyetujui atau menolak |
| `disetujui` | Pembayaran valid dan premium telah diaktifkan | Tidak ada perubahan status lagi |
| `ditolak` | Pembayaran tidak valid atau perlu diperbaiki | User dapat mengirim ulang bukti, kembali ke `menunggu_verifikasi` |
| `dibatalkan` | Order dibatalkan user sebelum diverifikasi | Terminal |

Aturan transisi wajib divalidasi di service/domain action, bukan hanya disembunyikan pada tombol UI.
Order tidak memiliki transisi kedaluwarsa otomatis dan dapat tetap berada pada status `menunggu_pembayaran` sampai dibayar atau dibatalkan user.

## Alur user

### 1. Halaman pilihan paket

- Menampilkan paket aktif dalam bentuk kartu/tabel: label, harga rupiah, dan durasi.
- Tombol `Pilih Paket` membuka ringkasan order.
- Paket nonaktif tidak dapat dipesan, termasuk melalui request yang dimanipulasi.

### 2. Halaman order

- Menampilkan ringkasan paket dan total pembayaran.
- User memilih salah satu metode pembayaran aktif.
- Menampilkan tujuan transfer dan instruksi dari entitas metode pembayaran, bukan hard-code di halaman atau konfigurasi aplikasi.
- Saat user menekan `Buat Order`, sistem membuat kode order dan snapshot paket.
- Sistem mencegah submit ganda dari halaman yang sama.

### 3. Konfirmasi pembayaran

- Hanya pemilik order yang dapat mengakses form.
- User mengunggah bukti pembayaran dan boleh menambahkan catatan.
- Validasi file: format JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB, serta penyimpanan privat menggunakan nama file acak.
- Setelah berhasil, status berubah menjadi `menunggu_verifikasi` dan `tanggal_konfirmasi` terisi.
- Untuk order yang ditolak, alasan penolakan ditampilkan dan user boleh mengganti bukti pembayaran.

### 4. Riwayat langganan user

- Hanya menampilkan order milik user login.
- Kolom: kode order, paket, harga, durasi, tanggal order, status, dan masa aktif hasil persetujuan.
- Filter status dan rentang tanggal.
- Halaman detail menampilkan timeline order, bukti milik sendiri, catatan, dan alasan penolakan.
- Aksi kontekstual: konfirmasi pembayaran, kirim ulang bukti, atau batalkan sesuai status.

## Alur admin

### 1. Kelola paket langganan

- Resource khusus admin untuk tambah, ubah, aktifkan, dan nonaktifkan paket.
- Paket tidak dihapus permanen; gunakan status nonaktif.
- Validasi label wajib, harga minimal Rp1, dan durasi minimal satu hari.

### 2. Kelola metode pembayaran

- Resource khusus admin untuk menambah, mengubah, mengurutkan, mengaktifkan, dan menonaktifkan rekening/metode transfer.
- Detail sensitif hanya ditampilkan kepada admin dan user yang memiliki order terkait.
- Metode yang sudah pernah digunakan tidak dapat dihapus; admin menonaktifkannya agar tidak tersedia bagi order baru.
- Perubahan metode pembayaran tidak mengubah snapshot tujuan pembayaran pada order lama.

### 3. Riwayat dan antrean verifikasi

- Admin dapat melihat seluruh order.
- Kolom: kode order, user, paket, harga, waktu konfirmasi, status, dan verifier.
- Filter status, paket, user, verifier, serta rentang tanggal.
- Default sorting menempatkan order `menunggu_verifikasi` yang paling lama di atas.
- Halaman detail menampilkan data user, snapshot paket, bukti pembayaran, catatan user, serta riwayat keputusan.

### 4. Verifikasi pembayaran

- Tombol `Setujui` hanya tersedia untuk status `menunggu_verifikasi`.
- Dalam satu transaksi database dan dengan row lock:
  1. Periksa ulang status order.
  2. Tentukan tanggal mulai dari nilai maksimum antara hari ini dan hari setelah `users.masa_aktif` saat ini.
  3. Hitung tanggal akhir menggunakan `durasi_hari` snapshot order.
  4. Ubah `users.type` menjadi `premium` dan `users.masa_aktif` ke tanggal akhir.
  5. Simpan verifier, waktu verifikasi, periode aktivasi, dan status `disetujui`.
- Tombol `Tolak` mewajibkan alasan dan tidak mengubah masa aktif user.
- Order yang sudah disetujui tidak dapat disetujui ulang atau ditolak.

## Hak akses

- User hanya dapat membaca dan mengubah order miliknya.
- User tidak dapat mengganti harga, durasi, status verifikasi, verifier, atau periode aktif melalui payload.
- Admin dapat membaca semua order dan mengelola paket.
- Hanya admin yang dapat menyetujui atau menolak pembayaran.
- Bukti pembayaran tidak ditempatkan di URL publik; unduhan harus melewati pemeriksaan policy.
- Seluruh query halaman user tetap diberi scope kepemilikan di sisi server.
- Aksi persetujuan juga memverifikasi `isAdmin()` di policy/service, bukan hanya berdasarkan visibilitas tombol Filament.

## Struktur implementasi yang disarankan

- Model: `PaketLangganan` dan `Langganan`.
- Model: `MetodePembayaran` untuk rekening dan metode transfer.
- Enum PHP: `StatusLangganan` untuk mencegah string status tersebar.
- Policy: `PaketLanggananPolicy` dan `LanggananPolicy`.
- Service/action:
  - `BuatOrderLangganan`
  - `KonfirmasiPembayaranLangganan`
  - `SetujuiLangganan`
  - `TolakLangganan`
- Filament admin:
  - `PaketLanggananResource`
  - `LanggananResource` atau halaman antrean verifikasi khusus admin
- Filament user:
  - halaman daftar paket
  - halaman order dengan pilihan metode pembayaran dan konfirmasi pembayaran
  - halaman riwayat dan detail langganan
- Notification database:
  - admin menerima notifikasi saat bukti dikirim
  - user menerima notifikasi saat order disetujui atau ditolak

## Informasi benefit reguler dan premium

Halaman paket menampilkan tabel benefit ringkas seperti pola perbandingan pada referensi AKUN.biz, tetapi menggunakan kemampuan APKu yang benar-benar tersedia:

| Benefit | Reguler | Premium |
| --- | --- | --- |
| Buku kas utama | Tersedia | Tersedia |
| Buku kas tambahan | Maksimal 1 tambahan gratis | Bebas menambah selama premium aktif |
| Dompet utama | Tersedia | Tersedia |
| Dompet tambahan | Maksimal 1 tambahan gratis | Bebas menambah selama premium aktif |
| Kelola transaksi pada buku kas/dompet tambahan | Terbatas pada tambahan gratis | Tersedia pada seluruh buku kas dan dompet milik user selama premium aktif |
| Pemasukan dan pengeluaran | Tersedia | Tersedia |
| Transfer antar-buku kas dan pemindahan antar-dompet | Tersedia sesuai batas akses akun | Tersedia pada seluruh buku kas dan dompet milik user selama premium aktif |
| Laporan harian, bulanan, tahunan, dan rentang khusus | Tersedia | Tersedia |
| Ekspor laporan PDF dan Excel | Tersedia | Tersedia |
| Pencatatan utang dan piutang | Tersedia | Tersedia |

Copy utama yang disarankan:

> Mulai dengan akun Reguler untuk mencatat keuangan harian. Aktifkan Premium ketika Anda membutuhkan lebih banyak buku kas dan dompet untuk memisahkan keuangan pribadi, usaha, tabungan, atau kebutuhan lainnya.

Tampilan tidak boleh menyatakan fitur bersama sebagai benefit eksklusif Premium. Jika aturan pembatasan premium berubah, tabel benefit dan `FITUR_APLIKASI.md` harus diperbarui bersamaan.

## Tahapan pengerjaan

### Tahap 1 — Fondasi data dan aturan domain

- Buat migration, enum, model, factory, relasi, policy, dan service/action.
- Tambahkan entitas metode pembayaran dan snapshot tujuan pembayaran pada order.
- Pastikan snapshot paket, transisi status, dan persetujuan idempoten.

### Tahap 2 — Pengelolaan paket dan metode pembayaran oleh admin

- Buat resource paket khusus admin.
- Buat resource rekening/metode pembayaran khusus admin.
- Tambahkan validasi serta mekanisme nonaktif paket.

### Tahap 3 — Order dan konfirmasi oleh user

- Buat halaman paket, ringkasan order, upload bukti, dan detail order.
- Terapkan penyimpanan privat dan policy unduhan.

### Tahap 4 — Verifikasi dan riwayat admin

- Buat antrean verifikasi, detail bukti, aksi setuju/tolak, filter, dan pencarian.
- Hubungkan persetujuan dengan pembaruan `users.type` serta `users.masa_aktif`.

### Tahap 5 — Notifikasi dan penyempurnaan

- Tambahkan notifikasi database.
- Tambahkan empty state, badge status, format rupiah, dan audit informasi verifier.
- Tambahkan tabel benefit Reguler dan Premium pada halaman pilihan paket.

### Tahap 6 — Dokumentasi fitur

- Perbarui `FITUR_APLIKASI.md` setelah implementasi agar memuat pemilihan paket, order, konfirmasi pembayaran, verifikasi admin, dan riwayat langganan.

## Rencana test terarah

### Model dan database

- Paket menolak harga/durasi tidak valid melalui validasi aplikasi.
- Harga paket di bawah Rp1 ditolak.
- Order menyimpan snapshot paket dengan benar.
- Order menyimpan snapshot metode dan tujuan pembayaran dengan benar.
- Relasi user, paket, metode pembayaran, order, dan verifier berfungsi.

### User

- User hanya melihat order sendiri.
- User tidak dapat membuka order atau bukti pembayaran milik user lain.
- Paket nonaktif tidak dapat dipesan.
- Metode pembayaran nonaktif tidak dapat dipilih pada order baru.
- User dapat mempunyai beberapa order aktif secara bersamaan.
- Order berhasil dibuat dengan status awal yang benar.
- Konfirmasi pembayaran memvalidasi jenis/ukuran file dan mengubah status.
- User dapat mengirim ulang bukti setelah ditolak.
- User tidak dapat mengonfirmasi order terminal.

### Admin

- User biasa tidak dapat membuka resource paket atau antrean admin.
- Admin dapat melihat seluruh order.
- Admin hanya dapat memverifikasi status `menunggu_verifikasi`.
- Penolakan wajib memiliki alasan dan tidak mengubah masa aktif.
- Persetujuan akun reguler mengubah akun menjadi premium.
- Persetujuan memperpanjang akun premium aktif tanpa menghilangkan sisa masa aktif.
- Persetujuan akun premium kedaluwarsa dihitung dari tanggal persetujuan.
- Request persetujuan ganda hanya memperpanjang masa aktif satu kali.
- Persetujuan yang selesai tidak menyediakan aksi pembatalan biasa.
- Kegagalan pembaruan membatalkan perubahan order dan user secara bersamaan.

### Antarmuka dan notifikasi

- Badge dan aksi sesuai status order.
- Admin menerima notifikasi setelah user mengonfirmasi pembayaran.
- User menerima notifikasi persetujuan atau penolakan.
- Daftar dan filter riwayat user/admin menghasilkan scope yang benar.

Test lokal dijalankan secara bertahap dengan `--filter` yang spesifik. Karena proyek menggunakan Pest v5, mulai dengan kombinasi `--filter` dan `--tia`; jika Pest memberi pesan bahwa TIA tidak berlaku untuk partial run, ulangi dan gunakan `--filter` bersama `--parallel` sesuai `AGENTS.md`.

## Kriteria penerimaan

- Admin dapat mengelola paket berisi label, harga, dan durasi.
- Admin dapat mengelola serta menonaktifkan rekening/metode pembayaran.
- User dapat membuat order dari paket aktif dan memperoleh kode order unik.
- User dapat memilih metode pembayaran aktif dan melihat instruksi pembayaran yang tersimpan sebagai snapshot order.
- User dapat mengirim bukti pembayaran secara aman.
- Admin dapat menyetujui atau menolak pembayaran dengan alasan yang tercatat.
- Persetujuan memperbarui premium dan masa aktif tepat satu kali.
- Riwayat user hanya berisi datanya sendiri; riwayat admin berisi seluruh transaksi.
- Snapshot membuat histori lama tidak berubah ketika paket diedit/nonaktif.
- Semua endpoint, action, file bukti, dan query terlindungi oleh policy/scope server-side.
- Dokumentasi fitur dan test terarah diperbarui bersama implementasi.
- Halaman pilihan paket menjelaskan benefit Reguler dan Premium sesuai aturan akses aplikasi yang berlaku.

## Keputusan bisnis yang telah ditetapkan

- Harga minimal Rp1.
- Durasi disimpan dalam hari.
- Rekening/metode transfer dikelola sebagai entitas tersendiri.
- Bukti pembayaran harus berformat JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB.
- Order tidak memiliki batas waktu kedaluwarsa otomatis.
- User boleh mempunyai beberapa order aktif sekaligus.
- Admin tidak dapat membatalkan persetujuan melalui alur biasa; koreksi memakai prosedur terpisah yang tercatat.
- Paket tidak dihapus dan dinonaktifkan ketika tidak lagi ditawarkan.
