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
- Akun admin awal dibuat saat migration menggunakan password dari `ADMIN_PASSWORD` pada environment.

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
- Import massal transaksi pemasukan dan pengeluaran melalui file CSV atau XLSX.
- Template XLSX dapat diunduh dari halaman transaksi sebagai acuan format data.
- File import divalidasi dan ditampilkan dalam pratinjau sebelum disimpan, termasuk jumlah baris, kesalahan, serta total pemasukan dan pengeluaran.
- Pengguna dapat mengunduh laporan error XLSX yang memuat nomor baris, data asli, dan alasan kegagalan untuk membantu memperbaiki file import.
- Header file dibaca otomatis dan dapat dipetakan ke kolom transaksi, sehingga file dengan nama kolom berbeda tetap dapat digunakan. Sistem memberikan saran untuk nama kolom umum dalam Bahasa Indonesia dan Inggris.
- Kategori yang belum tersedia dapat dibuat otomatis setelah pengguna mengaktifkan konfirmasi. Daftar kategori baru ditampilkan pada pratinjau dan pembuatannya ikut dibatalkan jika import transaksi gagal.
- Seluruh baris import disimpan secara atomik dan saldo buku kas serta dompet diperbarui menggunakan aturan transaksi yang sama dengan pencatatan manual.
- Import mengikuti kepemilikan dan hak pengelolaan buku kas, dompet, serta kategori pengguna. File yang sama tidak dapat diimpor lebih dari sekali.
- Riwayat import menampilkan nama file, waktu, jumlah transaksi, dan status setiap batch milik pengguna.
- Batch import yang masih lengkap dapat dibatalkan secara atomik. Seluruh transaksi dalam batch dihapus dan dampaknya pada saldo buku kas serta dompet dipulihkan.
- File dari batch yang sudah dibatalkan dapat diimpor kembali tanpa membuat catatan batch duplikat.
- File hingga 1.000 baris diproses langsung, sedangkan file 1.001–10.000 baris diproses melalui antrean privat. Status, progres, dan pesan kegagalannya dapat dipantau pada riwayat import.
- File import dibatasi maksimal 10 MB dan berkas antrean dihapus dari penyimpanan privat setelah selesai atau gagal diproses.
- Worker antrean memprioritaskan queue `import-transaksi`; batas retry disetel lebih panjang daripada batas waktu job agar batch yang masih berjalan tidak diproses ganda.
- Import belum mendukung transfer saldo antar-buku kas atau pemindahan saldo antar-dompet.
- Beberapa pengguna dapat mencatat transaksi pada buku kas yang sama melalui peran Editor.
- Setiap transaksi menyimpan identitas pengguna yang mencatatnya.
- Editor menggunakan dompet dan kategori miliknya sendiri serta hanya dapat mengubah atau menghapus transaksi buatannya.
- Informasi dompet anggota lain disamarkan pada daftar transaksi, pencarian, laporan, dan hasil ekspor.

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
- Pemilik dapat membagikan buku kas kepada pengguna APKu lain yang sudah terdaftar dan terverifikasi.
- Kolaborator memiliki peran **Viewer** atau **Editor**, dengan masa akses yang dapat dijadwalkan atau dibatasi.
- Viewer dapat melihat transaksi dan laporan buku bersama, sedangkan editor juga dapat mencatat transaksi.
- Buku bersama tidak dihitung sebagai kuota buku kas milik kolaborator.
- Pemilik dapat mengubah peran atau mencabut akses kolaborator kapan saja.

## 6. Dompet

- Membuat dan mengubah dompet sebagai representasi tempat penyimpanan uang, misalnya kas tunai atau rekening bank.
- Menampilkan saldo, status akses, deskripsi, dan penanda dompet default.
- Menjadikan dompet tertentu sebagai dompet default.
- Memindahkan saldo ke dompet lain sebelum menghapus dompet.
- Tetap menampilkan nama dompet yang sudah dihapus pada riwayat transaksi dan laporan.
- Pengguna dapat mengaudit saldo seluruh dompet yang dapat dikelola dengan memasukkan saldo riil hasil pengecekan di luar aplikasi.
- Audit menyimpan snapshot saldo aplikasi, saldo riil, selisih, tanggal, buku kas pencatatan, serta catatan umum dan catatan per dompet.
- Selisih positif dicatat sebagai pemasukan dan selisih negatif sebagai pengeluaran berkategori sistem **Audit Saldo** pada buku kas yang dipilih; buku kas utama dipilih secara default.
- Dompet tanpa selisih tetap tercatat dalam riwayat audit tanpa membuat transaksi penyesuaian.
- Seluruh penyesuaian dalam satu audit disimpan secara atomik dan dibatalkan jika saldo berubah selama proses audit.
- Transaksi penyesuaian saldo tidak dapat diubah atau dihapus langsung. Koreksi dilakukan melalui audit saldo baru agar jejak rekonsiliasi tetap terjaga.

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
- Admin memiliki akses lintas data untuk kebutuhan pemantauan dan administrasi, tetapi menu data keuangan pribadi tidak ditampilkan dalam navigasi admin agar paparan data diminimalkan.

## 11. Administrasi pengguna

Fitur berikut hanya tersedia untuk admin:

- Melihat daftar pengguna.
- Membuat, mengubah, dan menghapus pengguna.
- Mengatur tipe akun dan masa aktif.
- Melihat status verifikasi email.
- Dashboard admin menjadi halaman utama setelah login dan hanya menampilkan data agregat: jumlah pengguna, jumlah akun premium aktif, serta jumlah pembayaran yang menunggu verifikasi.
- Daftar pengguna tidak menampilkan jumlah buku kas, transaksi, atau utang-piutang dan tidak menyediakan aksi impersonasi.
- Navigasi admin difokuskan pada dashboard, pengguna, dan operasional langganan. Menu transaksi, pencarian transaksi, laporan, buku kas, dompet, kategori, utang, piutang, dan Akun Saya disembunyikan untuk admin.

## 12. Fitur pendukung

- Antarmuka berbahasa Indonesia.
- Opsi input pilihan yang berasal dari data master disimpan dalam cache selama 3 hari dan otomatis diperbarui ketika entitas terkait ditambah, diubah, atau dihapus.
- Pencarian cepat menu melalui Spotlight.
- Login cepat akun pengembangan pada lingkungan lokal.
- Pencatatan aktivitas dan debugging melalui Laravel Telescope serta Debugbar pada lingkungan yang sesuai.
- Notifikasi otomatis ke Telegram untuk exception yang dilaporkan pada lingkungan production apabila kredensial bot dan chat telah dikonfigurasi.

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
- Admin dapat mengelola voucher berisi label, tanggal kedaluwarsa opsional, persentase diskon, dan ketentuan pemakaian berulang.
- Satu voucher dapat memiliki banyak kode unik. Kode voucher berulang dapat dipakai berkali-kali oleh user mana pun, sedangkan kode non-berulang hanya dapat dipakai pada satu order yang tidak dibatalkan.
- User dapat memasukkan kode voucher saat membuat order. Kode, persentase, nominal diskon, dan total pembayaran disimpan sebagai snapshot order.

## Catatan implementasi

Transfer buku kas, pemindahan saldo dompet, dan import transaksi pada buku bersama hanya tersedia bagi pemilik buku pada versi awal fitur kolaborasi.

Rangkuman ini dibuat berdasarkan implementasi yang tersedia di source code proyek pada 5 September 2026.
