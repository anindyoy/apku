# Rangkuman Fitur Aplikasi APKu

APKu adalah aplikasi web untuk mencatat dan memantau keuangan pribadi melalui kas dan dompet. Aplikasi menyediakan pencatatan transaksi, laporan, pengelolaan utang-piutang, serta administrasi akun.

Dokumentasi dipisahkan berdasarkan tanggung jawab: backend mencakup aturan bisnis, hak akses, validasi, penyimpanan, dan proses server; frontend mencakup halaman, tampilan, navigasi, dan interaksi pengguna.

## Backend

Aturan bisnis dan pemrosesan data aplikasi.

### 1. Akun dan autentikasi

- Registrasi dan login pengguna, dengan validasi Cloudflare Turnstile Managed di server untuk registrasi.
- Verifikasi alamat email.
- Lupa dan reset password.
- Pengingat perpanjangan masa aktif pada H-30 dan H-7 sebelum masa aktif berakhir.
- Akun admin awal dibuat saat migration menggunakan password dari `ADMIN_PASSWORD` pada environment.
- Penyimpanan perubahan profil pengguna: nama, email, nomor HP, penggunaan aplikasi, dan password.

### 2. Onboarding pengguna baru

- Pengaturan awal mencakup pembuatan kas utama beserta deskripsi, dompet utama, saldo awal, serta aktivitas pemasukan dan pengeluaran yang sering digunakan.

### 3. Pengelolaan transaksi

- Mencatat pemasukan dan pengeluaran.
- Mengubah dan menghapus transaksi sesuai hak akses.
- Transfer saldo antar-kas.
- Pemindahan saldo antar-dompet.
- Import massal transaksi pemasukan dan pengeluaran melalui file CSV atau XLSX.
- Aktivitas yang belum tersedia dapat dibuat otomatis setelah konfirmasi pengguna. Pembuatannya ikut dibatalkan jika import transaksi gagal.
- Seluruh baris import disimpan secara atomik dan saldo kas serta dompet diperbarui menggunakan aturan transaksi yang sama dengan pencatatan manual.
- Import mengikuti kepemilikan dan hak pengelolaan kas, dompet, serta aktivitas pengguna. File yang sama tidak dapat diimpor lebih dari sekali.
- Batch import yang masih lengkap dapat dibatalkan secara atomik. Seluruh transaksi dalam batch dihapus dan dampaknya pada saldo kas serta dompet dipulihkan.
- File dari batch yang sudah dibatalkan dapat diimpor kembali tanpa membuat catatan batch duplikat.
- File hingga 1.000 baris diproses langsung, sedangkan file 1.001–10.000 baris diproses melalui antrean privat.
- File import dibatasi maksimal 10 MB dan berkas antrean dihapus dari penyimpanan privat setelah selesai atau gagal diproses.
- Worker antrean memprioritaskan queue `import-transaksi`; batas retry disetel lebih panjang daripada batas waktu job agar batch yang masih berjalan tidak diproses ganda.
- Import belum mendukung transfer saldo antar-kas atau pemindahan saldo antar-dompet.
- Beberapa pengguna dapat mencatat transaksi pada kas yang sama melalui peran Editor.
- Setiap transaksi menyimpan identitas pengguna yang mencatatnya.
- Editor menggunakan dompet dan aktivitas miliknya sendiri serta hanya dapat mengubah atau menghapus transaksi buatannya.
- Informasi dompet anggota lain disamarkan pada daftar transaksi, pencarian, laporan, dan hasil ekspor.
- Transfer kas menentukan dompet pencatatan secara otomatis, sedangkan transfer dompet menentukan kas pencatatan secara otomatis. Transaksi pada kas terbatas tidak dapat diubah atau dihapus.
- File import divalidasi sebelum disimpan.
- Header file import dibaca otomatis dan mendukung pemetaan nama kolom yang berbeda ke kolom transaksi.

### 4. Kas

- Membuat, melihat, dan mengubah kas.
- Menentukan kas utama/default.
- Menghapus kas kosong secara langsung.
- Memindahkan seluruh transaksi dan saldo ke kas lain sebelum menghapus kas yang masih berisi transaksi.
- Melalui fitur **Kolaborator Kas**, pemilik dapat membagikan kas kepada pengguna APKu lain yang sudah terdaftar dan terverifikasi.
- Kolaborator memiliki peran **Viewer** atau **Editor**, dengan masa akses yang dapat dijadwalkan atau dibatasi.
- Viewer dapat melihat transaksi dan laporan buku bersama, sedangkan editor juga dapat mencatat transaksi.
- Kas bersama tidak dihitung sebagai kuota kas milik kolaborator.
- Pemilik dapat mengubah peran atau mencabut akses kolaborator kapan saja.
- Satu kas dapat memiliki beberapa tabungan emas logam mulia dengan label emas (contoh: Emas Antam), tanpa atribut kadar. Berat mendukung maksimal empat angka desimal dan saat pembuatan dicatat sebagai saldo awal tanpa modal. Saldo awal dapat dicatat oleh pemilik untuk tabungan yang beratnya masih nol.
- Layanan internal pembelian dan penjualan emas beserta riwayatnya tetap tersedia; pembelian mengurangi saldo rupiah kas dan dompet, sedangkan penjualan menambah saldo rupiah secara atomik.
- Modal emas mencakup harga dasar, biaya cetak, premium pecahan, administrasi, dan biaya transaksi.
- Pengguna dapat mengambil harga buyback emas dari endpoint publik, menggunakan snapshot terakhir ketika layanan gagal, atau menyimpan harga manual privat untuk kas terkait.
- Kestabilan endpoint harga emas dipantau setiap enam jam melalui smoke test terpisah yang memvalidasi ketersediaan, waktu respons, struktur data, dan kesegaran harga buyback.
- Setelah pemantauan berhasil, workflow menghapus run sukses sebelumnya agar daftar GitHub Actions tidak menumpuk. Run terbaru dan seluruh run gagal tetap disimpan; kegagalan pembersihan tidak menggagalkan hasil pemantauan.
- Kas yang masih memiliki emas hanya dapat dihapus setelah tabungan emasnya ikut dipindahkan ke kas lain milik pengguna yang sama.
- Tanggal pembelian tabungan emas menggunakan `created_at` dan tidak boleh di masa depan. Total gram per kas tetap menghitung seluruh tabungan dalam kas saat pencarian atau pergantian halaman.
- Perhitungan nilai emas tidak mencatat perubahan harga sebagai transaksi.

### 5. Dompet

- Membuat dan mengubah dompet sebagai representasi tempat penyimpanan uang, misalnya kas tunai atau rekening bank.
- Menjadikan dompet tertentu sebagai dompet default.
- Memindahkan saldo ke dompet lain sebelum menghapus dompet.
- Audit menyimpan snapshot saldo aplikasi, saldo riil, selisih, tanggal, kas pencatatan, serta catatan umum dan catatan per dompet.
- Selisih positif audit dicatat sebagai pemasukan dan selisih negatif sebagai pengeluaran dengan aktivitas sistem **Audit Saldo** pada kas yang dipilih.
- Dompet tanpa selisih tetap tercatat dalam riwayat audit tanpa membuat transaksi penyesuaian.
- Seluruh penyesuaian dalam satu audit disimpan secara atomik dan dibatalkan jika saldo berubah selama proses audit.
- Transaksi penyesuaian saldo tidak dapat diubah atau dihapus langsung. Koreksi dilakukan melalui audit saldo baru agar jejak rekonsiliasi tetap terjaga.

### 6. Aktivitas transaksi

- Menggunakan aktivitas sebagai klasifikasi transaksi dan bahan ringkasan laporan.
- Pengelolaan data aktivitas pemasukan dan pengeluaran mencakup penambahan, perubahan, dan penghapusan.

### 7. Laporan keuangan

- Ekspor laporan ke PDF berformat lanskap.
- Ekspor laporan ke Excel (`.xlsx`).
- Perhitungan laporan mengikuti periode harian, bulanan, tahunan, atau rentang tanggal khusus serta filter kas dan dompet. Data mencakup saldo awal, pemasukan, pengeluaran, akumulasi, saldo akhir, rincian transaksi, dan ringkasan per aktivitas beserta persentasenya.

### 8. Utang dan piutang

- Menyimpan nama pihak terkait, nominal awal, tanggal pencatatan, dan tanggal jatuh tempo opsional.
- Menambah nominal utang/piutang atau mencatat pembayaran.
- Mengubah atau menghapus catatan dan detail riwayat.

### 9. Hak akses dan tipe akun

- Data pengguna biasa dibatasi otomatis agar hanya menampilkan data miliknya.
- Akun reguler tetap dapat mengelola kas utama, satu kas tambahan gratis, dompet utama, dan satu dompet tambahan gratis.
- Akun dengan masa aktif premium dapat membuat dan mengelola kas serta dompet tambahan.
- Admin memiliki akses lintas data untuk kebutuhan pemantauan dan administrasi.

### 10. Administrasi pengguna

Fitur berikut hanya tersedia untuk admin:

- Membuat, mengubah, dan menghapus pengguna.
- Mengatur tipe akun dan masa aktif.

### 11. Fitur pendukung

- Opsi input pilihan yang berasal dari data master disimpan dalam cache selama 3 hari dan otomatis diperbarui ketika entitas terkait ditambah, diubah, atau dihapus.
- Pencatatan aktivitas dan debugging melalui Laravel Telescope serta Debugbar pada lingkungan yang sesuai.
- Notifikasi otomatis ke Telegram untuk exception yang dilaporkan pada lingkungan production apabila kredensial bot dan chat telah dikonfigurasi.

### 12. Langganan premium

- Admin mengelola paket langganan yang terdiri dari label, harga minimal Rp1, durasi dalam hari, dan status aktif.
- Admin mengelola rekening atau metode pembayaran manual, termasuk bank, dompet digital, QR, dan instruksi transfer.
- User dapat membuat beberapa order aktif dengan memilih paket dan metode pembayaran yang tersedia.
- Detail paket, harga, durasi, dan tujuan pembayaran disimpan sebagai snapshot agar riwayat lama tidak berubah ketika data master diperbarui.
- Bukti pembayaran disimpan secara privat dan hanya dapat dilihat oleh pemilik order atau admin.
- Admin dapat menyetujui atau menolak pembayaran yang menunggu verifikasi.
- Persetujuan mengaktifkan akun Premium atau memperpanjang masa aktif tanpa menghilangkan sisa masa aktif yang masih tersedia.
- Riwayat user hanya menampilkan order miliknya, sedangkan admin dapat melihat seluruh order.
- Order tidak kedaluwarsa otomatis. Paket dan metode pembayaran yang tidak lagi digunakan dinonaktifkan, bukan dihapus.
- Admin dapat mengelola voucher berisi label, tanggal kedaluwarsa opsional, persentase diskon, dan ketentuan pemakaian berulang.
- Satu voucher dapat memiliki banyak kode unik. Kode voucher berulang dapat dipakai berkali-kali oleh user mana pun, sedangkan kode non-berulang hanya dapat dipakai pada satu order yang tidak dibatalkan.
- Kode voucher, persentase, nominal diskon, dan total pembayaran disimpan sebagai snapshot order.
- Validasi bukti pembayaran menerima format JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB.

### 13. Dashboard pengguna

- Lima transaksi terbaru milik pengguna diurutkan berdasarkan tanggal transaksi, dengan ID terbaru sebagai pembeda apabila tanggal sama.
- Pengaturan visibilitas dan urutan bagian dashboard disimpan per akun dan tetap berlaku ketika halaman dibuka kembali.

### Catatan implementasi

Seeder demo menyediakan 1–2 tabungan emas dengan saldo awal tanpa modal dan dua kolaborator kas aktif (Viewer dan Editor) untuk setiap pengguna non-admin. Setiap pengguna juga menerima akses ke dua kas pengguna lain.

Transfer kas, pemindahan saldo dompet, dan import transaksi pada kas bersama hanya tersedia bagi pemilik kas pada versi awal fitur kolaborasi.

## Frontend

Tampilan dan alur interaksi pengguna aplikasi.

### 1. Akun dan autentikasi

- Pengaturan profil melalui halaman **Akun Saya**, meliputi nama, email, nomor HP, penggunaan aplikasi, dan perubahan password.
- Tampilan tipe akun dan masa aktif akun premium.
- Notifikasi di dalam aplikasi.
- Halaman registrasi dengan Cloudflare Turnstile Managed, login, verifikasi email, lupa password, dan reset password.

### 2. Onboarding pengguna baru

Pengguna baru diarahkan ke wizard pengaturan awal sebelum menggunakan fitur utama. Proses ini mencakup:

- Pembuatan kas utama beserta deskripsinya.
- Pembuatan dompet utama.
- Pencatatan saldo awal.
- Pembuatan aktivitas pemasukan yang sering digunakan.
- Pembuatan aktivitas pengeluaran yang sering digunakan.

### 3. Pengelolaan transaksi

- Menentukan tanggal, kas, dompet, aktivitas, nominal, dan deskripsi transaksi.
- Mencatat beberapa transaksi berurutan melalui aksi **Tambah yang lain**.
- Tombol **Tambah transaksi** tetap tersedia selama pengguna memiliki kas yang dapat dikelola, termasuk ketika daftar difilter ke kas terbatas. Pilihan kas di modal hanya memuat kas yang dapat dikelola; aksi ubah dan hapus tidak tersedia untuk transaksi pada kas terbatas.
- Pemasukan, pengeluaran, transfer kas, dan transfer dompet dicatat melalui satu modal **Tambah transaksi**; jenis transaksi menentukan field serta warna form. Seluruh input dan tombol submit dinonaktifkan sementara ketika perubahan jenis sedang diproses. Transfer kas hanya meminta kas asal dan tujuan, sedangkan transfer dompet hanya meminta dompet asal dan tujuan.
- Menampilkan saldo berjalan untuk kas atau dompet yang sedang dipilih.
- Penanda visual pada ikon dan teks record: pemasukan berwarna hijau, pengeluaran berwarna merah, transfer kas berwarna biru, dan transfer dompet berwarna kuning.
- Nama aktivitas pada daftar transaksi selalu ditampilkan dengan huruf awal kapital.
- Tabel transaksi menggunakan lima kolom utama; informasi pencatat ditampilkan di bawah tanggal, sedangkan dompet ditampilkan di bawah kas. Saat filter kas atau dompet aktif, saldo berjalan ditampilkan di bawah nominal.
- Filter transaksi berdasarkan bulan, tahun, kas, dan dompet.
- Navigasi cepat ke periode sebelumnya atau berikutnya.
- Pencarian berdasarkan deskripsi pada daftar transaksi.
- Template XLSX dapat diunduh dari halaman transaksi sebagai acuan format data.
- Pratinjau import sebelum disimpan menampilkan jumlah baris, kesalahan, serta total pemasukan dan pengeluaran.
- Pengguna dapat mengunduh laporan error XLSX yang memuat nomor baris, data asli, dan alasan kegagalan untuk membantu memperbaiki file import.
- Pengguna dapat memetakan header file ke kolom transaksi dan melihat saran untuk nama kolom umum dalam Bahasa Indonesia dan Inggris.
- Riwayat import menampilkan nama file, waktu, jumlah transaksi, dan status setiap batch milik pengguna.
- Pratinjau import menampilkan daftar aktivitas baru dan konfirmasi untuk membuatnya otomatis.
- Status, progres, dan pesan kegagalan pemrosesan antrean dapat dipantau pada riwayat import.

### 4. Pencarian transaksi global

- Mencari transaksi berdasarkan deskripsi, aktivitas, kas, dompet, tipe transaksi, nominal, atau pengguna.
- Memfilter hasil berdasarkan jenis transaksi, kas, dan dompet.
- Hasil pencarian memakai lima kolom dan format yang sama seperti daftar transaksi: pencatat berada di bawah tanggal, dompet di bawah kas, serta deskripsi di bawah aktivitas.
- Menampilkan tipe transaksi dan warna teks hasil sesuai tipe tersebut dengan pola yang sama seperti daftar transaksi.
- Pengguna dapat mengubah atau menghapus transaksi yang dapat dikelolanya langsung dari hasil pencarian.
- Menampilkan pengguna pemilik transaksi khusus untuk admin.
- Mengurutkan hasil berdasarkan transaksi terbaru.

### 5. Kas

- Menampilkan saldo serta jumlah transaksi pada setiap kas.
- Form tambah dan ubah kolaborator dibuka melalui modal pada daftar **Kolaborator Kas**; akses dapat dicabut melalui aksi pada baris atau pilihan massal.
- Daftar tabungan emas dikelompokkan berdasarkan kas dengan total gram seluruh tabungan pada setiap grup, tanpa kolom Kas terpisah. Daftar menampilkan tanggal **Dibeli pada**, harga beli, dan keterangan. Tanggal pembelian dapat diisi saat membuat dan mengubah tabungan, dengan nilai awal waktu sekarang. Harga beli dan keterangan opsional. Berat ditampilkan tanpa nol desimal berlebih, misalnya `1` atau `0,5` gram; total modal tidak ditampilkan pada daftar.
- Aksi Beli emas, Jual emas, dan Histori tidak tersedia pada halaman tabungan emas.
- Aksi cek nilai emas menampilkan total berat, nilai pasar emas, modal, estimasi untung/rugi, saldo rupiah, dan total nilai kas gabungan.
- Form tabungan emas menempatkan berat gram setelah Kas, tanpa nilai default, dan menerima desimal koma seperti `0,5`. Aksi saldo awal tersedia untuk tabungan yang beratnya masih nol.
- Halaman kas menyediakan pembuatan, perubahan, pemilihan kas utama/default, dan penghapusan kas; kas berisi transaksi atau emas menyediakan alur pemindahan sebelum penghapusan.

### 6. Dompet

- Menampilkan saldo, status akses, deskripsi, dan penanda dompet default.
- Tetap menampilkan nama dompet yang sudah dihapus pada riwayat transaksi dan laporan.
- Pengguna dapat mengaudit saldo seluruh dompet yang dapat dikelola dengan memasukkan saldo riil hasil pengecekan di luar aplikasi.
- Halaman dompet menyediakan pembuatan, perubahan, pemilihan dompet default, serta pemindahan saldo sebelum penghapusan.
- Form audit saldo memilih kas utama secara default dan menyediakan saldo riil, tanggal, kas pencatatan, catatan umum, serta catatan per dompet.

### 7. Aktivitas transaksi

- Aktivitas pemasukan dan pengeluaran dikelola secara terpisah.
- Menambah, mengubah, dan menghapus aktivitas.

### 8. Laporan keuangan

- Laporan harian, bulanan, tahunan, atau rentang tanggal khusus.
- Filter laporan berdasarkan kas dan dompet.
- Ringkasan saldo awal, total pemasukan, total pengeluaran, akumulasi, dan saldo akhir.
- Ringkasan pemasukan dan pengeluaran per aktivitas beserta persentasenya.
- Tab aktivitas yang mengelompokkan rincian transaksi pemasukan dan pengeluaran berdasarkan aktivitas dalam daftar yang dapat dibuka dan ditutup, dengan rincian tertutup secara default.
- Indikator loading ditampilkan rata kiri pada baris tersendiri di bawah tab dan di atas isi laporan ketika memproses perubahan filter, periode, tab, navigasi, atau ekspor.
- Rincian transaksi pada periode yang dipilih.
- Navigasi ke periode sebelum atau sesudah periode aktif.
- Aksi ekspor laporan ke PDF lanskap dan Excel (`.xlsx`).

### 9. Utang dan piutang

- Pencatatan utang dan piutang pada menu terpisah.
- Menampilkan total utang atau piutang dan status selesai/belum selesai.
- Halaman detail berisi riwayat penambahan dan pembayaran.
- Menampilkan saldo tersisa secara berjalan pada setiap riwayat.
- Pencarian berdasarkan nama pihak terkait.

### 10. Hak akses dan tipe akun

- Status akses dompet ditampilkan sebagai **Aktif** atau **Terbatas**.
- Menu data keuangan pribadi disembunyikan dari navigasi admin agar paparan data diminimalkan; rincian navigasi tersedia pada bagian Administrasi pengguna.

### 11. Administrasi pengguna

Fitur berikut hanya tersedia untuk admin:

- Melihat daftar pengguna.
- Melihat status verifikasi email.
- Dashboard admin menjadi halaman utama setelah login dan hanya menampilkan data agregat: jumlah pengguna, jumlah akun premium aktif, serta jumlah pembayaran yang menunggu verifikasi.
- Daftar pengguna tidak menampilkan jumlah kas, transaksi, atau utang-piutang dan tidak menyediakan aksi impersonasi.
- Navigasi admin difokuskan pada dashboard, pengguna, dan operasional langganan. Menu transaksi, pencarian transaksi, laporan, kas, dompet, aktivitas, utang, piutang, dan Akun Saya disembunyikan untuk admin.
- Admin dapat membuat, mengubah, dan menghapus pengguna serta mengatur tipe akun dan masa aktif.

### 12. Fitur pendukung

- Antarmuka berbahasa Indonesia.
- Pencarian cepat menu melalui Spotlight.
- Login cepat akun pengembangan pada lingkungan lokal. Saat `APP_DEMO=true`, akun admin tidak ditampilkan dalam pilihan login cepat.

### 13. Langganan premium

- User dapat membandingkan benefit akun Reguler dan Premium berdasarkan batas kas serta dompet.
- Form pengiriman bukti pembayaran mendukung JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB.
- User dan admin menerima notifikasi dalam aplikasi saat pembayaran dikonfirmasi, disetujui, atau ditolak sesuai perannya.
- Pengguna dapat membuat order dengan memilih paket dan metode pembayaran yang tersedia serta memasukkan kode voucher.
- Riwayat order pengguna menampilkan order miliknya; admin dapat melihat seluruh order dan menyetujui atau menolak pembayaran yang menunggu verifikasi.
- Admin memiliki halaman pengelolaan paket langganan, rekening atau metode pembayaran manual, serta voucher dan kode uniknya.

### 14. Dashboard pengguna

- Dashboard menjadi halaman utama pengguna biasa setelah login dan onboarding.
- Layout dashboard menggunakan tiga kolom pada layar besar, dua kolom pada tablet, dan satu kolom pada ponsel. Tabel transaksi memakai lebar penuh agar lima kolomnya tetap mudah dibaca.
- Menampilkan seluruh kas dan dompet milik pengguna beserta saldo masing-masing, termasuk yang akses pengelolaannya terbatas. Kartu juga menampilkan total saldo dan jumlah kas atau dompet, dengan ikon serta warna aksen berbeda dan dukungan tema gelap.
- Menampilkan total sisa utang dan piutang setelah pembayaran, masing-masing disertai tiga catatan dengan aktivitas terbaru.
- Menampilkan status langganan premium, tanggal akhir masa aktif, dan sisa hari; akun tanpa langganan atau kedaluwarsa ditampilkan sebagai Reguler.
- Tabel menampilkan lima transaksi terbaru milik pengguna dengan definisi kolom yang sama dengan daftar transaksi: tipe, tanggal dan pencatat, kas dan dompet, aktivitas dan deskripsi, serta nominal.
- Melalui **Atur dashboard**, pengguna dapat menampilkan atau menyembunyikan setiap bagian serta mengubah urutannya dengan geser atau tombol urutan. Seluruh bagian tampil secara default.
- Baris tabel transaksi dashboard menyediakan aksi **Ubah** dan **Hapus** yang sama dengan daftar transaksi, sesuai hak pengelolaan pengguna. Aksi tersebut tidak tersedia untuk transaksi audit saldo.
- Kartu transaksi menyediakan tombol rata kanan di atas tabel: **Tambah** untuk membuka modal pencatatan yang sama dengan daftar transaksi (mengikuti hak akses kas), dan **Lihat lengkap** untuk menuju daftar transaksi.
- Setiap kartu dashboard pengguna dapat dilipat atau dibuka melalui header atau tombol panah. Semua kartu terbuka secara default; melipat kartu tetap menampilkan judulnya.
- Dashboard admin tetap hanya berisi statistik agregat administrasi.

Rangkuman ini dibuat berdasarkan implementasi yang tersedia di source code proyek pada 5 September 2026. Struktur dokumentasi dipisahkan menjadi backend dan frontend pada 12 September 2026.
