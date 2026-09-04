# Rangkuman Fitur Aplikasi APKu

APKu adalah aplikasi web untuk mencatat dan memantau keuangan pribadi melalui buku kas dan dompet. Aplikasi menyediakan pencatatan transaksi, laporan, pengelolaan utang-piutang, serta administrasi akun.

## 1. Akun dan autentikasi

- Registrasi dan login pengguna.
- Verifikasi alamat email.
- Lupa dan reset password.
- Pengaturan profil melalui halaman **Akun Saya**, meliputi nama, email, nomor HP, penggunaan aplikasi, dan perubahan password.
- Tampilan tipe akun dan masa aktif akun premium.
- Notifikasi di dalam aplikasi.
- Pengingat perpanjangan masa aktif pada H-30 dan H-7 sebelum masa aktif berakhir.

## 2. Onboarding pengguna baru

Pengguna baru diarahkan ke wizard pengaturan awal sebelum menggunakan fitur utama. Proses ini mencakup:

- Pembuatan buku kas utama beserta deskripsinya.
- Pembuatan dompet utama.
- Pencatatan saldo awal.
- Pembuatan kategori pemasukan yang sering digunakan.
- Pembuatan kategori pengeluaran yang sering digunakan.

## 3. Pengelolaan transaksi

- Mencatat pemasukan dan pengeluaran.
- Menentukan tanggal, buku kas, dompet, kategori, nominal, dan deskripsi transaksi.
- Mengubah dan menghapus transaksi sesuai hak akses.
- Mencatat beberapa transaksi berurutan melalui aksi **Tambah yang lain**.
- Transfer saldo antar-buku kas.
- Pemindahan saldo antar-dompet.
- Menampilkan saldo berjalan untuk buku kas atau dompet yang sedang dipilih.
- Penanda visual untuk pemasukan, pengeluaran, dan transfer.
- Filter transaksi berdasarkan bulan, tahun, buku kas, dan dompet.
- Navigasi cepat ke periode sebelumnya atau berikutnya.
- Pencarian berdasarkan deskripsi pada daftar transaksi.

## 4. Pencarian transaksi global

- Mencari transaksi berdasarkan deskripsi, kategori, buku kas, dompet, tipe transaksi, nominal, atau pengguna.
- Memfilter hasil berdasarkan jenis transaksi, buku kas, dan dompet.
- Menampilkan pengguna pemilik transaksi khusus untuk admin.
- Mengurutkan hasil berdasarkan transaksi terbaru.

## 5. Buku kas

- Membuat, melihat, dan mengubah buku kas.
- Menampilkan saldo serta jumlah transaksi pada setiap buku kas.
- Menentukan buku kas utama/default.
- Menghapus buku kas kosong secara langsung.
- Memindahkan seluruh transaksi dan saldo ke buku kas lain sebelum menghapus buku kas yang masih berisi transaksi.

## 6. Dompet

- Membuat dan mengubah dompet sebagai representasi tempat penyimpanan uang, misalnya kas tunai atau rekening bank.
- Menampilkan saldo, status akses, deskripsi, dan penanda dompet default.
- Menjadikan dompet tertentu sebagai dompet default.
- Memindahkan saldo ke dompet lain sebelum menghapus dompet.
- Tetap menampilkan nama dompet yang sudah dihapus pada riwayat transaksi dan laporan.

## 7. Kategori transaksi

- Kategori pemasukan dan pengeluaran dikelola secara terpisah.
- Menambah, mengubah, dan menghapus kategori.
- Menggunakan kategori sebagai klasifikasi transaksi dan bahan ringkasan laporan.

## 8. Laporan keuangan

- Laporan harian, bulanan, tahunan, atau rentang tanggal khusus.
- Filter laporan berdasarkan buku kas dan dompet.
- Ringkasan saldo awal, total pemasukan, total pengeluaran, akumulasi, dan saldo akhir.
- Ringkasan pemasukan dan pengeluaran per kategori beserta persentasenya.
- Rincian transaksi pada periode yang dipilih.
- Navigasi ke periode sebelum atau sesudah periode aktif.
- Ekspor laporan ke PDF berformat lanskap.
- Ekspor laporan ke Excel (`.xlsx`).

## 9. Utang dan piutang

- Pencatatan utang dan piutang pada menu terpisah.
- Menyimpan nama pihak terkait, nominal awal, tanggal pencatatan, dan tanggal jatuh tempo opsional.
- Menampilkan total utang atau piutang dan status selesai/belum selesai.
- Halaman detail berisi riwayat penambahan dan pembayaran.
- Menambah nominal utang/piutang atau mencatat pembayaran.
- Menampilkan saldo tersisa secara berjalan pada setiap riwayat.
- Mengubah atau menghapus catatan dan detail riwayat.
- Pencarian berdasarkan nama pihak terkait.

## 10. Hak akses dan tipe akun

- Data pengguna biasa dibatasi otomatis agar hanya menampilkan data miliknya.
- Akun reguler tetap dapat mengelola buku kas utama, satu buku kas tambahan gratis, dompet utama, dan satu dompet tambahan gratis.
- Akun dengan masa aktif premium dapat membuat dan mengelola buku kas serta dompet tambahan.
- Status akses dompet ditampilkan sebagai **Aktif** atau **Terbatas**.
- Admin memiliki akses lintas data untuk pemantauan dan administrasi, tetapi aksi transaksi operasional tertentu disembunyikan dari admin.

## 11. Administrasi pengguna

Fitur berikut hanya tersedia untuk admin:

- Melihat daftar pengguna.
- Membuat, mengubah, dan menghapus pengguna.
- Mengatur tipe akun dan masa aktif.
- Melihat status verifikasi email.
- Melihat jumlah buku kas, transaksi, dan utang-piutang setiap pengguna.
- Masuk sebagai pengguna lain melalui fitur impersonasi untuk kebutuhan dukungan atau pemeriksaan.

## 12. Fitur pendukung

- Antarmuka berbahasa Indonesia.
- Pencarian cepat menu melalui Spotlight.
- Login cepat akun pengembangan pada lingkungan lokal.
- Pencatatan aktivitas dan debugging melalui Laravel Telescope serta Debugbar pada lingkungan yang sesuai.

## 13. Langganan premium

- User dapat membandingkan benefit akun Reguler dan Premium berdasarkan batas buku kas serta dompet.
- Admin mengelola paket langganan yang terdiri dari label, harga minimal Rp1, durasi dalam hari, dan status aktif.
- Admin mengelola rekening atau metode pembayaran manual, termasuk bank, dompet digital, QR, dan instruksi transfer.
- User dapat membuat beberapa order aktif dengan memilih paket dan metode pembayaran yang tersedia.
- Detail paket, harga, durasi, dan tujuan pembayaran disimpan sebagai snapshot agar riwayat lama tidak berubah ketika data master diperbarui.
- User dapat mengirim bukti pembayaran berformat JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB.
- Bukti pembayaran disimpan secara privat dan hanya dapat dilihat oleh pemilik order atau admin.
- Admin dapat menyetujui atau menolak pembayaran yang menunggu verifikasi.
- Persetujuan mengaktifkan akun Premium atau memperpanjang masa aktif tanpa menghilangkan sisa masa aktif yang masih tersedia.
- Riwayat user hanya menampilkan order miliknya, sedangkan admin dapat melihat seluruh order.
- Order tidak kedaluwarsa otomatis. Paket dan metode pembayaran yang tidak lagi digunakan dinonaktifkan, bukan dihapus.
- User dan admin menerima notifikasi dalam aplikasi saat pembayaran dikonfirmasi, disetujui, atau ditolak sesuai perannya.

## Catatan implementasi

Terdapat modul **Share Buku** dengan pilihan hak akses `viewer` dan `editor`. Resource dan penyimpanan datanya sudah tersedia, tetapi menu navigasinya disembunyikan dan mekanisme berbagi tersebut belum terhubung ke aturan akses buku kas utama. Karena itu, fitur ini belum dianggap sebagai fitur pengguna yang aktif.

Rangkuman ini dibuat berdasarkan implementasi yang tersedia di source code proyek pada 4 September 2026.
