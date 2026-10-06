# Rangkuman Fitur Aplikasi APKu

APKu adalah aplikasi web untuk mencatat dan memantau keuangan pribadi melalui kas dan dompet. Aplikasi menyediakan pencatatan transaksi, laporan, pengelolaan utang-piutang, serta administrasi akun.

Dokumentasi dipisahkan berdasarkan tanggung jawab: backend mencakup aturan bisnis, hak akses, validasi, penyimpanan, dan proses server; frontend mencakup halaman, tampilan, navigasi, dan interaksi pengguna.

## Backend

Aturan bisnis dan pemrosesan data aplikasi.

### 1. Akun dan autentikasi

- Registrasi dan login pengguna, dengan verifikasi Cloudflare Turnstile Managed pada form dan validasi server hanya di environment production.
- Verifikasi alamat email.
- Lupa dan reset password.
- Email verifikasi akun, email reset kata sandi, pesan validasi formulir, status autentikasi, dan navigasi halaman bawaan Laravel ditampilkan dalam bahasa Indonesia.
- Pengingat perpanjangan masa aktif pada H-30 dan H-7 sebelum masa aktif berakhir.
- Akun admin awal dibuat saat migration menggunakan password dari `ADMIN_PASSWORD` pada environment.
- Penyimpanan perubahan profil pengguna: nama, email, nomor HP, penggunaan aplikasi, dan password.

### 2. Onboarding pengguna baru

- Pengaturan awal mencakup pembuatan kas utama beserta deskripsi, dompet utama, saldo awal, serta kategori pemasukan dan pengeluaran yang sering digunakan.

### 3. Pengelolaan transaksi

- Mencatat pemasukan dan pengeluaran.
- Form transaksi menyediakan pembuatan kategori langsung dari pilihan Kategori untuk semua pengguna non-admin; kategori otomatis terhubung ke kas yang dipilih dan tipe awal mengikuti jenis transaksi.
- Pengguna Premium aktif dapat membuat kas dan dompet dari pilihan pada form transaksi. Kas baru dimulai dengan saldo nol dan seluruh kategori milik pemilik kas otomatis dihubungkan; dompet baru dimulai dengan saldo nol. Opsi ini tetap mematuhi kuota akun dan validasi nama unik.
- Transaksi baru memengaruhi saldo secara default, termasuk saldo awal dan transfer kas; pengecualian tersedia untuk import riwayat tanpa dampak saldo.
- Mengubah dan menghapus transaksi sesuai hak akses.
- Saldo per baris pada daftar transaksi mengikuti saldo kas dan dompet tersimpan serta dampak transaksi menurut tanggal; penghapusan transaksi di tengah riwayat memperbarui saldo baris berikutnya, termasuk saat daftar difilter.
- Filter periode, kas, dan dompet pada daftar transaksi berada dalam panel Filter transaksi yang tertutup saat halaman pertama dibuka dan dapat dibuka sesuai kebutuhan.
- Pada layar desktop, header dan isi tabel Transaksi, Pencarian Transaksi, serta transaksi terbaru di Dashboard sejajar: Tipe, Tanggal, Kas, Kategori, dan Nominal. Kolom ringkasan Transaksi hanya tampil di layar kecil.
- Pada layar mobile, daftar Transaksi, hasil Pencarian Transaksi, dan lima transaksi terbaru di Dashboard menampilkan jenis, nominal, kategori, dan tanggal secara ringkas tanpa perlu menggeser tabel; rincian lain tersedia melalui aksi Ubah jika hak akses memungkinkan.
- Transfer saldo antar-kas.
- Pemindahan saldo antar-dompet.
- Import massal transaksi pemasukan dan pengeluaran melalui file CSV atau XLSX.
- Modal import transaksi menyediakan unduhan contoh template XLSX sebelum pengguna mengunggah file. Template memuat tiga baris data transaksi dengan deskripsi terisi maupun kosong; nilai buku kas dan dompetnya sesuai opsi milik pengguna yang ditampilkan di kolom referensi setelah satu kolom pemisah, bersama kategori pemasukan/pengeluaran. Baris referensi tidak dihitung sebagai transaksi saat file diimpor.
- Kolom kategori pada import bersifat opsional; nilai kosong diimpor sebagai transaksi tanpa kategori. Kategori dicari pada kas di baris yang sama. Kategori yang belum tersedia dapat dibuat otomatis, dan kategori yang belum terhubung dapat dihubungkan ke kas pada baris transaksinya, setelah konfirmasi pengguna. Perubahan ini ikut dibatalkan jika import transaksi gagal.
- Seluruh baris import disimpan secara atomik. Pengguna dapat memilih apakah import memperbarui saldo kas dan dompet; pilihan aktif secara default. Riwayat yang diimpor tanpa dampak saldo tetap tampil sebagai transaksi dan tidak mengubah saldo saat diubah, dihapus, atau saat batch dibatalkan.
- Import mengikuti kepemilikan dan hak pengelolaan kas, dompet, serta kategori pengguna. File yang sama tidak dapat diimpor lebih dari sekali.
- Batch import yang masih lengkap dapat dibatalkan secara atomik. Seluruh transaksi dalam batch dihapus dan dampaknya pada saldo kas serta dompet dipulihkan jika opsi pembaruan saldo diaktifkan.
- File dari batch yang sudah dibatalkan dapat diimpor kembali tanpa membuat catatan batch duplikat.
- File hingga 1.000 baris diproses langsung, sedangkan file 1.001–10.000 baris diproses melalui antrean privat.
- File import dibatasi maksimal 10 MB dan berkas antrean dihapus dari penyimpanan privat setelah selesai atau gagal diproses.
- Worker antrean memprioritaskan queue `import-transaksi`; batas retry disetel lebih panjang daripada batas waktu job agar batch yang masih berjalan tidak diproses ganda.
- Import belum mendukung transfer saldo antar-kas atau pemindahan saldo antar-dompet.
- Beberapa pengguna dapat mencatat transaksi pada kas yang sama melalui peran Editor.
- Setiap transaksi menyimpan identitas pengguna yang mencatatnya.
- Editor menggunakan dompet miliknya sendiri dan kategori milik kas bersama, dapat mengelola kategori kas tersebut, serta hanya dapat mengubah atau menghapus transaksi buatannya.
- Informasi dompet anggota lain disamarkan pada daftar transaksi, pencarian, laporan, dan hasil ekspor.
- Transfer kas menentukan dompet pencatatan secara otomatis, sedangkan transfer dompet menentukan kas pencatatan secara otomatis. Transaksi pada kas terbatas tidak dapat diubah atau dihapus.
- File import divalidasi sebelum disimpan.
- Header file import dibaca otomatis dan mendukung pemetaan nama kolom yang berbeda ke kolom transaksi.

### 4. Kas

- Membuat, melihat, dan mengubah kas.
- Menentukan kas utama/default.
- Menghapus kas kosong secara langsung.
- Memindahkan seluruh transaksi dan saldo ke kas lain sebelum menghapus kas yang masih berisi transaksi.
- Saat pemindahan, kategori yang belum terhubung ke kas tujuan dipetakan satu per satu ke kategori kas tujuan atau dikosongkan sesuai pilihan pengguna; kategori yang sudah terhubung ke kas tujuan dipertahankan.
- Kas baru dapat langsung memakai seluruh kategori pemiliknya melalui pilihan **Pakai semua kategori saya di kas ini** (aktif secara default).
- Melalui fitur **Kolaborator Kas**, hanya pemilik dengan masa aktif premium yang masih berlaku dapat membuat kolaborasi kas, baik kepada pengguna APKu lain yang sudah terdaftar dan terverifikasi maupun melalui link publik. Tombol tambah tidak tersedia untuk akun reguler atau premium kedaluwarsa, dan pembuatan juga ditolak oleh otorisasi server. Kolaborasi yang sudah ada tetap dapat dikelola dan dicabut oleh pemilik.
- Email kolaborator boleh kosong untuk membuat link kas publik bertoken acak. Akses publik selalu **Viewer**, tanpa login, dan diperiksa pada setiap permintaan berdasarkan tanggal mulai serta berakhir. Mencabut akses langsung menonaktifkan link; kolaborasi dengan email tetap privat.
- Email kolaborator diketik melalui input teks tanpa daftar email pengguna. Setelah input ditinggalkan, status terdaftar ditampilkan berwarna hijau atau belum terdaftar berwarna merah. Jika diisi, email yang belum terdaftar gagal validasi; pembatasan akun terverifikasi, bukan diri sendiri, dan bukan admin tetap berlaku.
- Kolaborator memiliki peran **Viewer** atau **Editor**, dengan masa akses yang dapat dijadwalkan atau dibatasi. Input **Mulai Berlaku** dan **Berakhir Pada** menggunakan tanggal saja tanpa jam; tanggal berakhir opsional dan harus setelah tanggal mulai.
- Viewer dapat melihat transaksi dan laporan buku bersama, sedangkan editor juga dapat mencatat transaksi.
- Kas bersama tidak dihitung sebagai kuota kas milik kolaborator.
- Pemilik dapat mengubah peran atau mencabut akses kolaborator kapan saja.
- Satu kas dapat memiliki beberapa tabungan emas logam mulia dengan label emas (contoh: Emas Antam), tanpa atribut kadar. Berat mendukung maksimal empat angka desimal dan saat pembuatan dicatat sebagai saldo awal tanpa modal. Saldo awal dapat dicatat oleh pemilik untuk tabungan yang beratnya masih nol.
- Layanan internal pembelian dan penjualan emas beserta riwayatnya tetap tersedia; pembelian mengurangi saldo rupiah kas dan dompet, sedangkan penjualan menambah saldo rupiah secara atomik.
- Modal emas mencakup harga dasar, biaya cetak, premium pecahan, administrasi, dan biaya transaksi.
- Pengguna dapat mengambil harga buyback emas dari endpoint publik, menggunakan snapshot terakhir ketika layanan gagal, atau menyimpan harga manual privat untuk kas terkait.
- Admin dapat mengubah URL API, URL sumber, timeout (1–60 detik), dan durasi cache harga emas (1–168 jam) melalui Setting. Nilai disimpan di database dan berlaku global; kolom kosong mengikuti default `services.harga_emas`. Perubahan pengaturan membatalkan cache harga terkait dan digunakan pada permintaan berikutnya.
- Kestabilan endpoint harga emas dipantau setiap hari oleh scheduler Laravel melalui command `harga-emas:pantau`, yang memvalidasi ketersediaan, waktu respons, struktur data, dan kesegaran harga buyback. Kegagalan pemantauan mengirim notifikasi melalui Telegram jika kredensial tersedia. Workflow GitHub Actions tetap dapat dijalankan manual untuk smoke test endpoint.
- Setelah pemantauan berhasil, workflow menghapus run sukses sebelumnya agar daftar GitHub Actions tidak menumpuk. Run terbaru dan seluruh run gagal tetap disimpan; kegagalan pembersihan tidak menggagalkan hasil pemantauan.
- Kas yang masih memiliki emas hanya dapat dihapus setelah tabungan emasnya ikut dipindahkan ke kas lain milik pengguna yang sama.
- Tanggal pembelian tabungan emas menggunakan `created_at` dan tidak boleh di masa depan. Total gram per kas tetap menghitung seluruh tabungan dalam kas saat pencarian atau pergantian halaman.
- Perhitungan nilai emas tidak mencatat perubahan harga sebagai transaksi.

### 5. Dompet

- Membuat dan mengubah dompet sebagai representasi tempat penyimpanan uang, misalnya kas tunai atau rekening bank.
- Menjadikan dompet tertentu sebagai dompet default.
- Memindahkan saldo ke dompet lain sebelum menghapus dompet.
- Audit baru dapat dibuat melalui tombol **Tambah audit** pada daftar Audit Saldo atau tombol **Audit saldo** pada halaman Dompet, menggunakan formulir dan aturan akses yang sama.
- Audit menyimpan snapshot saldo aplikasi, saldo riil, selisih, tanggal, kas pencatatan, serta catatan umum dan catatan per dompet.
- Selisih positif audit dicatat sebagai pemasukan dan selisih negatif sebagai pengeluaran tanpa kategori pada kas yang dipilih. Transaksi ini dikenali dari relasinya ke audit dan tampil dengan label **Audit Saldo** pada daftar, laporan, dan ekspor.
- Dompet tanpa selisih tetap tercatat dalam riwayat audit tanpa membuat transaksi penyesuaian.
- Seluruh penyesuaian dalam satu audit disimpan secara atomik dan dibatalkan jika saldo berubah selama proses audit.
- Transaksi penyesuaian saldo tidak dapat diubah atau dihapus langsung. Koreksi dilakukan melalui audit saldo baru agar jejak rekonsiliasi tetap terjaga.

### 6. Kategori transaksi

- Menggunakan kategori sebagai klasifikasi transaksi yang opsional dan bahan ringkasan laporan. Transaksi tanpa kategori tampil sebagai **Tanpa kategori**; transfer kas dan transfer dompet selalu tanpa kategori.
- Kategori terikat pada kas melalui tabel `kategori_kas`: transaksi hanya dapat memakai kategori yang terhubung ke kasnya, dan satu kategori dapat dipakai di beberapa kas milik pemilik yang sama.
- Kategori dimiliki pemilik kas (`user_id`) dan mencatat pembuatnya (`dibuat_oleh`). Pemilik dan Editor aktif dapat menambah, mengubah, melepas, dan menghapus kategori kas; Editor hanya dapat mengubah atau menghapus kategori yang seluruh kasnya dapat ia sunting. Viewer hanya melihat.
- Opsi kategori pada form transaksi disimpan dalam cache per kas dan dibersihkan saat kategori atau hubungannya dengan kas berubah.
- Kolom `tipe` menerima **Pemasukan**, **Pengeluaran**, atau **Semua** (berlaku untuk kedua jenis transaksi). Setiap pengguna memiliki pengaturan pisah/gabung tipe yang hanya mengatur tampilan: memfilter dropdown kategori pada form transaksi sesuai jenisnya atau menampilkan semuanya, serta menentukan tipe default saat membuat kategori baru. Mengalihkan pengaturan ini tidak mengubah data kategori yang sudah ada. Kategori bertipe Semua selalu ikut tampil pada dropdown walau difilter sesuai tipe transaksi.

### 7. Laporan keuangan

- Ekspor laporan ke PDF berformat lanskap.
- Ekspor laporan ke Excel (`.xlsx`).
- Perhitungan laporan mengikuti periode harian, bulanan, tahunan, atau rentang tanggal khusus serta filter kas dan dompet. Data mencakup saldo awal, pemasukan, pengeluaran, akumulasi, saldo akhir, rincian transaksi, dan ringkasan per kategori beserta persentasenya.

### 8. Utang dan piutang

- Menyimpan nama pihak terkait, nominal awal, tanggal pencatatan, dan tanggal jatuh tempo opsional.
- Daftar Utang dan Piutang tampil sebagai kartu pada mobile dan tabel pada desktop. Padding vertikal isi kartu dibuat rapat agar lebih padat. Setiap kartu memuat status, tanggal, pembaruan terakhir, pihak terkait, jatuh tempo jika tersedia, deskripsi, nominal tersisa, serta aksi Detail dan Hapus.
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
- Pencatatan log dan debugging melalui Laravel Telescope serta Debugbar pada lingkungan yang sesuai.
- Notifikasi otomatis ke Telegram untuk exception yang dilaporkan pada lingkungan production apabila kredensial bot dan chat telah dikonfigurasi.

### 12. Langganan premium

- Admin mengelola paket langganan yang terdiri dari label, harga minimal Rp1, durasi dalam hari, dan status aktif.
- Migration menyediakan pilihan awal 1 Tahun (365 hari), 9 Bulan (270 hari), 6 Bulan (180 hari), dan 3 Bulan (90 hari), dengan harga sementara Rp0 dan status nonaktif. Admin harus mengisi harga sebelum mengaktifkan paket. Paket dengan label yang sudah ada tidak ditimpa; rollback mempertahankan data paket.
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

Hook Git yang diaktifkan melalui `core.hooksPath=.githooks` menjalankan `composer2 update --no-interaction` jika pull/merge/rebase mengubah `composer.json` atau `composer.lock`, kemudian `php artisan migrate --force --no-interaction` jika ada file PHP baru di `database/migrations`. Kegagalan Composer menghentikan migrasi otomatis. Panduan aktivasi dan penanganan kegagalan tersedia di [docs/git-hooks.md](docs/git-hooks.md).

Seeder demo menyediakan 1–2 tabungan emas dengan saldo awal tanpa modal dan dua kolaborator kas aktif (Viewer dan Editor) untuk setiap pengguna non-admin. Setiap pengguna juga menerima akses ke dua kas pengguna lain.

Transfer kas, pemindahan saldo dompet, dan import transaksi pada kas bersama hanya tersedia bagi pemilik kas pada versi awal fitur kolaborasi.

## Frontend

Tampilan dan alur interaksi pengguna aplikasi.

### 1. Akun dan autentikasi

- Pengaturan profil melalui halaman **Akun Saya**, meliputi nama, nomor HP, penggunaan aplikasi, dan perubahan password. Email ditampilkan tetapi tidak dapat diubah dari halaman ini.
- Tampilan tipe akun dengan huruf awal kapital dan masa aktif akun premium dalam format tanggal tahun-bulan-tanggal (`YYYY-MM-DD`).
- Notifikasi di dalam aplikasi.
- Halaman registrasi dengan Cloudflare Turnstile Managed hanya di production, login, verifikasi email, lupa password, dan reset password.

### 2. Onboarding pengguna baru

Pengguna baru diarahkan ke wizard pengaturan awal sebelum menggunakan fitur utama. Proses ini mencakup:

- Pembuatan kas utama beserta deskripsinya.
- Pembuatan dompet utama.
- Pencatatan saldo awal.
- Pembuatan kategori pemasukan yang sering digunakan.
- Pembuatan kategori pengeluaran yang sering digunakan.

### 3. Pengelolaan transaksi

- Menentukan tanggal, kas, dompet, nominal, dan deskripsi transaksi. Kategori opsional; pilihannya mengikuti kas yang dipilih dan dikosongkan jika tidak terhubung ke kas baru.
- Mencatat beberapa transaksi berurutan melalui aksi **Tambah yang lain**.
- Tombol **Tambah transaksi** tetap tersedia selama pengguna memiliki kas yang dapat dikelola, termasuk ketika daftar difilter ke kas terbatas. Pilihan kas di modal hanya memuat kas yang dapat dikelola; aksi ubah dan hapus tidak tersedia untuk transaksi pada kas terbatas.
- Pemasukan, pengeluaran, transfer kas, dan transfer dompet dicatat melalui satu modal **Tambah transaksi**; jenis transaksi menentukan field serta warna form. Pilihan jenis transaksi tersusun grid dua kolom pada semua ukuran layar, termasuk mobile agar tidak meluap ke samping. Seluruh input dan tombol submit dinonaktifkan sementara ketika perubahan jenis sedang diproses. Transfer kas hanya meminta kas asal dan tujuan, sedangkan transfer dompet hanya meminta dompet asal dan tujuan.
- Modal **Ubah transaksi** pada daftar transaksi, dashboard, dan pencarian memakai dua kolom pada layar yang cukup lebar serta satu kolom pada layar sempit. Deskripsi menggunakan lebar penuh, dan input nominal menampilkan prefix **Rp** seperti form Tambah.
- Pada modal **Ubah transaksi**, kas dan dompet dapat diganti. Transfer kas menampilkan kas asal, kas tujuan, dan dompet; transfer dompet menampilkan kas, dompet asal, dan dompet tujuan. Perubahan memperbarui pasangan transfer dan saldo terkait secara atomik. Kas dan dompet ketiga dan seterusnya tetap terlihat tetapi tidak dapat dipilih saat masa Premium tidak berlaku; aturan hak akses juga diperiksa saat penyimpanan.
- Menampilkan saldo berjalan untuk kas atau dompet yang sedang dipilih.
- Penanda visual pada ikon dan teks record: pemasukan berwarna hijau, pengeluaran berwarna merah, transfer kas berwarna biru, dan transfer dompet berwarna kuning.
- Nama kategori pada daftar transaksi selalu ditampilkan dengan huruf awal kapital.
- Tabel transaksi menggunakan lima kolom utama; informasi pencatat ditampilkan di bawah tanggal hanya pada kas yang sedang atau pernah dikolaborasikan dengan akun lain (termasuk setelah kedaluwarsa atau dicabut, tidak termasuk tautan publik), sedangkan dompet ditampilkan di bawah kas. Saat filter kas atau dompet aktif, saldo berjalan ditampilkan di bawah nominal.
- Filter transaksi berdasarkan bulan, tahun, kas, dan dompet; pilihan bulan dan tahun dibuat lebih ringkas, chevron filter lebih jelas, dan tombol reset sejajar di kanan ketika ruang tersedia.
- Navigasi cepat ke periode sebelumnya atau berikutnya.
- Pencarian berdasarkan deskripsi pada daftar transaksi.
- Template XLSX dapat diunduh dari halaman transaksi sebagai acuan format data; baris contohnya memakai tanggal `YYYY-MM-DD` tanpa waktu.
- Form import menyediakan pilihan **Perbarui saldo kas dan dompet** yang aktif secara default. Pilihan dapat dimatikan untuk riwayat lama agar saldo saat ini tidak berubah. Pilihan ini dan **Buat kategori yang belum tersedia** tampil dalam dua kolom pada layar lebar.
- Pratinjau import sebelum disimpan menampilkan jumlah baris, kesalahan, serta total pemasukan dan pengeluaran. Tanggal import menerima `YYYY-MM-DD` atau `YYYY-MM-DD HH:mm`; waktu yang tidak diisi menjadi `00:00`. Jika beberapa baris memiliki format tanggal salah, contoh kedua format ditampilkan sekali sebelum daftar masalah.
- Nilai kas atau dompet yang kosong pada baris import memakai kas atau dompet default pengguna yang dapat dikelola.
- Pengguna dapat mengunduh laporan error XLSX yang memuat nomor baris, data asli, dan alasan kegagalan untuk membantu memperbaiki file import.
- Pengguna dapat memetakan header file ke kolom transaksi dan melihat saran untuk nama kolom umum dalam Bahasa Indonesia dan Inggris.
- Pemetaan kolom pada modal import tersusun dalam tiga kolom di layar lebar dan menyesuaikan jumlah kolom pada layar lebih kecil.
- Riwayat import menampilkan nama file, waktu, jumlah transaksi, dampak saldo, dan status setiap batch milik pengguna.
- Riwayat Import tidak ditampilkan pada navbar; pengguna membukanya melalui aksi **Riwayat Import** pada halaman Transaksi.
- Pratinjau import menampilkan daftar kategori baru beserta kasnya, daftar kategori yang akan dihubungkan ke kas, dan konfirmasi untuk memprosesnya otomatis.
- Status, progres, dan pesan kegagalan pemrosesan antrean dapat dipantau pada riwayat import.

### 4. Pencarian transaksi global

- Mencari transaksi berdasarkan deskripsi, kategori, kas, dompet, tipe transaksi, nominal, atau pengguna.
- Memfilter hasil berdasarkan jenis transaksi, kas, dan dompet.
- Hasil pencarian memakai lima kolom dan format yang sama seperti daftar transaksi: pencatat berada di bawah tanggal hanya untuk kas dengan riwayat kolaborasi akun lain, dompet di bawah kas, serta deskripsi di bawah kategori.
- Menampilkan tipe transaksi dan warna teks hasil sesuai tipe tersebut dengan pola yang sama seperti daftar transaksi.
- Pengguna dapat mengubah atau menghapus transaksi yang dapat dikelolanya langsung dari hasil pencarian.
- Menampilkan pengguna pemilik transaksi khusus untuk admin.
- Mengurutkan hasil berdasarkan transaksi terbaru.

### 5. Kas

- Daftar kas menggunakan card Filament responsif yang menampilkan nama dan deskripsi kas, akses dan kepemilikan, serta saldo dan jumlah transaksi dengan label yang jelas. Jumlah produk emas hanya tampil jika lebih dari nol. Data setiap halaman, pencarian, dan urutan list disimpan dalam cache hingga tiga hari dan diperbarui saat kas, transaksi, tabungan emas, atau akses bersama berubah. Pencarian tetap mencakup nama dan deskripsi kas; tanggal dibuat dan diperbarui tersedia sebagai kolom opsional.
- Aksi pada card `/admin/buku-kas` dikelompokkan dalam menu **Aksi**, dengan pilihan yang mengikuti hak akses dan kondisi kas.
- Daftar **Kolaborator Kas** tersedia di `/admin/pengaturan/kolaborator-kas` sebagai card responsif: satu kolom di ponsel, dua di layar sedang, dan tiga di layar lebar. Setiap card menampilkan kas, nama dan email kolaborator atau penanda publik, hak akses, status, serta tanggal **Mulai** dan **Berlaku hingga**. Form tambah dan ubah kolaborator dibuka melalui modal; akses dapat dicabut melalui aksi pada card atau pilihan massal.
- Daftar kolaborator menyediakan tombol **Salin link** pada setiap kolaborator publik, dengan notifikasi keberhasilan atau kegagalan penyalinan, tanpa kolom URL terpisah. Tanggal mulai dan berakhir pada card ditampilkan tanpa jam. Halaman publik menampilkan saldo kas, transaksi berhalaman dengan ringkasan padat pada layar mobile, filter bulan, pencarian berdasarkan deskripsi/kategori/jenis transaksi, serta total pemasukan dan pengeluaran bulan tersebut (termasuk transfer), tanpa aksi perubahan data. Nama dompet dan identitas pengguna tidak ditampilkan. Saat kata pencarian diisi, pencarian mencakup seluruh tanggal pada kas tersebut tanpa dibatasi bulan terpilih dan tetap dipertahankan saat berpindah halaman. Menghapus pencarian mengembalikan filter bulan; ringkasan total bulanan tetap mengikuti bulan terpilih.
- Daftar tabungan emas dikelompokkan berdasarkan kas dengan total gram seluruh tabungan pada setiap grup, tanpa kolom Kas terpisah. Di mobile, setiap tabungan tampil sebagai kartu bertumpuk dengan label emas, berat, harga beli, keterangan, tanggal **Dibeli pada**, dan aksi sesuai hak akses; di desktop tetap berupa tabel. Tanggal pembelian dapat diisi saat membuat dan mengubah tabungan, dengan nilai awal waktu sekarang. Harga beli dan keterangan opsional. Berat ditampilkan tanpa nol desimal berlebih, misalnya `1` atau `0,5` gram; total modal tidak ditampilkan pada daftar.
- Aksi Beli emas, Jual emas, dan Histori tidak tersedia pada halaman tabungan emas.
- Aksi cek nilai emas menampilkan total nilai kas dalam ringkasan beraksen emas, kartu nilai emas dan saldo rupiah, rincian berat/harga/modal, serta estimasi untung/rugi berwarna sesuai hasil. Modal responsif mendukung mode gelap dan menampilkan status sumber harga serta waktu berlakunya. Label sumber harga API menjadi tautan ke URL sumber dari Setting, dengan default `services.harga_emas.source` (`HARGA_EMAS_SOURCE`), dan dibuka di tab baru; sumber manual tetap berupa teks.
- Form tabungan emas menempatkan berat gram setelah Kas, tanpa nilai default, dan menerima desimal koma seperti `0,5`. Aksi saldo awal tersedia untuk tabungan yang beratnya masih nol.
- Halaman kas menyediakan pembuatan, perubahan, pemilihan kas utama/default, dan penghapusan kas; kas berisi transaksi atau emas menyediakan alur pemindahan sebelum penghapusan.
- Daftar kas dan modal tambah menjelaskan sisa slot atau batas kuota kas untuk pengguna Reguler dalam panel padat berisi satu kalimat. Panel berlatar kuning saat tersisa 1 slot dan merah saat batas tercapai, dengan dukungan mode gelap. Kas bersama tidak mengisi kuota. Saat batas tercapai, daftar menampilkan pesan bahwa pengguna reguler tidak bisa menambah lagi karena kuota sudah terpenuhi dan aksi tambah disembunyikan; validasi server tetap berlaku. Premium aktif tidak menampilkan pesan kuota dan memiliki slot tidak terbatas, sedangkan Premium kedaluwarsa kembali mengikuti batas Reguler.

### 6. Dompet

- Daftar dompet menggunakan card Filament responsif yang menampilkan saldo, status akses, deskripsi, dan status dompet default dengan label yang jelas. Data setiap halaman, pencarian, dan urutan list disimpan dalam cache hingga tiga hari dan diperbarui saat data dompet berubah.
- Tetap menampilkan nama dompet yang sudah dihapus pada riwayat transaksi dan laporan.
- Pengguna dapat mengaudit saldo seluruh dompet yang dapat dikelola dengan memasukkan saldo riil hasil pengecekan di luar aplikasi.
- Halaman dompet menyediakan pembuatan, perubahan, pemilihan dompet default, serta pemindahan saldo sebelum penghapusan.
- Daftar dompet dan modal tambah menjelaskan sisa slot atau batas kuota dompet untuk pengguna Reguler dalam panel padat berisi satu kalimat. Panel berlatar kuning saat tersisa 1 slot dan merah saat batas tercapai, dengan dukungan mode gelap. Saat batas tercapai, daftar menampilkan pesan bahwa pengguna reguler tidak bisa menambah lagi karena kuota sudah terpenuhi dan aksi tambah disembunyikan; validasi server tetap berlaku. Premium aktif tidak menampilkan pesan kuota dan memiliki slot tidak terbatas, sedangkan Premium kedaluwarsa kembali mengikuti batas Reguler.
- Form audit saldo memilih kas utama secara default dan menyediakan saldo riil, tanggal, kas pencatatan, catatan umum, serta catatan per dompet.
- Audit Saldo tidak ditampilkan pada navbar. Akses audit tersedia melalui tombol **Audit saldo** dan **Riwayat audit** pada halaman Dompet; daftar riwayat tetap menyediakan tombol **Tambah audit**.

### 7. Kategori transaksi

- Kategori bertipe Pemasukan, Pengeluaran, atau Semua dikelola dalam satu tabel berisi nama, tipe, dan kas yang terhubung, dengan filter kas dan tipe.
- Daftar kategori menampilkan jumlah transaksi sebagai keterangan di bawah nama (misalnya **4 transaksi**), tanpa kolom jumlah terpisah. Nama panjang dapat turun baris. Kategori kas bersama menampilkan nama pemiliknya.
- Form tambah dan ubah memuat nama, tipe, dan daftar centang kas. Semua kas yang dipilih harus milik pemilik yang sama. Tipe default kategori baru mengikuti pengaturan tampilan pengguna.
- Tombol **Pengaturan tampilan** pada header halaman membuka toggle untuk memisahkan atau menggabungkan tipe kategori pada dropdown transaksi; pengaturan ini tidak mengubah data kategori yang sudah ada.
- Melepas kategori dari kas yang transaksinya masih memakainya diblokir. Aksi **Lepas dari kas** menyediakan pilihan untuk mengosongkan kategori pada transaksi kas tersebut terlebih dahulu.
- Menghapus kategori yang masih dipakai menawarkan kategori pengganti yang terhubung ke semua kas terkait; tanpa pengganti, transaksinya menjadi tanpa kategori.
- Menambah, mengubah, dan menghapus kategori.
- Pengguna dapat menambahkan kategori langsung dari form transaksi; kategori baru otomatis terhubung ke kas transaksi dan tersedia untuk jenis transaksi yang dipilih atau tipe Semua.

### 8. Laporan keuangan

- Grup menu **Laporan** berada tepat di bawah grup **Utang Piutang** pada navigasi.
- Laporan harian, bulanan, tahunan, atau rentang tanggal khusus.
- Filter laporan berdasarkan kas dan dompet.
- Ringkasan saldo awal, total pemasukan, total pengeluaran, akumulasi, dan saldo akhir.
- Ringkasan pemasukan dan pengeluaran per kategori beserta persentasenya, termasuk baris **Tanpa kategori** pada ringkasan, rincian, PDF, dan Excel.
- Tab kategori yang mengelompokkan rincian transaksi pemasukan dan pengeluaran berdasarkan kategori dalam daftar yang dapat dibuka dan ditutup, dengan rincian tertutup secara default.
- Data laporan dimuat otomatis setelah halaman dan filter tampil; query transaksi serta perhitungan ringkasan ditunda hingga permintaan berikutnya. Indikator Memuat laporan... tampil sejak halaman dibuka sampai data tersedia.
- Indikator loading ditampilkan rata kiri pada baris tersendiri di bawah tab dan di atas isi laporan ketika memproses perubahan filter, periode, tab, navigasi, atau ekspor.
- Rincian transaksi pada periode yang dipilih.
- Navigasi ke periode sebelum atau sesudah periode aktif.
- Aksi ekspor laporan ke PDF lanskap dan Excel (`.xlsx`).

### 9. Utang dan piutang

- Pencatatan utang dan piutang pada menu terpisah.
- Aksi tabel **Detail** dan **Hapus** pada daftar utang dan piutang ditampilkan dengan ikon beserta label teks.
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
- Navigasi admin difokuskan pada dashboard, pengguna, operasional langganan, dan Setting. Menu transaksi, pencarian transaksi, laporan, kas, dompet, kategori, utang, piutang, dan Akun Saya disembunyikan untuk admin.
- Admin dapat membuat, mengubah, dan menghapus pengguna serta mengatur tipe akun dan masa aktif.
- Submenu **Harga Emas** (`/admin/pengaturan/setting`) pada halaman Setting khusus admin menyediakan form pengaturan harga emas. Nilai default ditampilkan sebagai placeholder; kosongkan kolom dan simpan untuk kembali memakai default.

### 12. Fitur pendukung

- Tombol aksi pada kartu daftar Kas, Dompet, dan Langganan turun ke baris berikutnya ketika ruang sempit, termasuk saat subnavigasi Setting terbuka, sehingga tetap berada di dalam kartu.

- Menu **Setting** (/admin/pengaturan) menggantikan grup Pengaturan pada navbar dan menggunakan Filament Cluster untuk membuka halaman pertama yang dapat diakses dengan subnavigasi bawaan pada setiap halaman anggota. URL halaman anggota memakai awalan `/admin/pengaturan/`, dan breadcrumb Setting kembali ke halaman pertama yang tersedia. Reguler dan Premium melihat Kas, Dompet, Kategori, Kolaborator Kas, Tabungan Emas, dan Akun Saya; admin melihat Pengguna dan Harga Emas. Submenu mengikuti otorisasi halaman tujuan. Audit saldo dan riwayatnya tetap diakses dari Dompet.

- Antarmuka berbahasa Indonesia.
- Halaman publik `/` menampilkan landing page APKu dengan ringkasan fitur, perbandingan akun Reguler dan Premium, logo aplikasi, serta tautan ke registrasi, login, dan tutorial.
- Warna utama panel Filament dan halaman tutorial menggunakan palet teal yang sama dengan warna utama landing page.
- Nominal rupiah ditampilkan tanpa digit desimal, termasuk total langganan, harga paket, audit saldo, dan harga beli emas. Perubahan format tampilan tidak mengubah nilai tersimpan atau presisi berat emas.
- Halaman publik **Tutorial Penggunaan** di `/tutorial` dapat dibaca tanpa login, dengan 18 topik fitur pengguna non-admin (Reguler, Premium, dan kas bersama), langkah bernomor, contoh, serta catatan hak akses. Konten dikelola melalui `resources/content/tutorial.json` dan ditampilkan sebagai kartu responsif dengan dukungan tema gelap, daftar isi tanpa nomor topik, serta bab berdasarkan halaman fitur yang dapat dibuka dan ditutup sebagai accordion. Pencarian hanya menampilkan bab berisi hasil. Tautan tersedia pada halaman login dan navigasi pengguna non-admin, serta tombol berikon tanda tanya di sisi kanan topbar panel untuk pengguna yang sudah login, termasuk admin. Menu Tutorial Penggunaan pada sidebar membuka daftar tutorial di tab baru. Tombol tutorial di topbar membuka panduan di tab baru dan langsung menuju topik sesuai URL halaman pengguna saat diklik, termasuk halaman detail/tambah dan perpindahan navigasi panel; bab tujuan otomatis terbuka. Sidebar tutorial menampilkan posisi topik dari jumlah hasil yang ditampilkan dan penanda aktif yang mengikuti tautan topik serta scroll. Topik tujuan ditandai secara visual; halaman tanpa pemetaan membuka daftar tutorial.
- Seluruh input pilihan kas dan dompet menggunakan dropdown tanpa pencarian, termasuk pilihan kas kolaborator dan tujuan pemindahan sebelum penghapusan.
- Pencarian cepat menu melalui Spotlight.
- Login cepat akun pengembangan pada lingkungan lokal. Saat `APP_DEMO=true`, akun admin tidak ditampilkan dalam pilihan login cepat.

### 13. Langganan premium

- Grup menu **Langganan** berada paling bawah pada navigasi, setelah menu **Setting**.

- User dapat membandingkan benefit akun Reguler dan Premium melalui tabel responsif dengan kolom Fitur, Free (Reguler), dan Premium di atas daftar kartu langganan, dengan aksen Premium dan dukungan mode gelap. Baris Jumlah kas dan Jumlah Dompet menampilkan Maksimal 2 untuk Free dan Tidak terbatas untuk Premium aktif. Lebar bagian perbandingan dibatasi maksimal 52rem; status ditampilkan sebagai ceklis hijau atau silang merah dengan label aksesibel, sedangkan kuota dan ketentuan tetap berupa teks. Tabel mencakup 9 fitur: jumlah kas, jumlah dompet, import transaksi, laporan/ekspor, utang/piutang, audit saldo, tabungan emas, pembuatan kolaborasi kas, dan pembuatan link kas publik. Reguler mencakup kas utama + 1 kas tambahan gratis dan dompet utama + 1 dompet tambahan gratis; Premium aktif membuka penambahan kas/dompet serta pembuatan kolaborasi privat atau link publik. Tabel dan catatan hak akses menjelaskan bahwa kolaborasi lama tetap dapat dikelola/dicabut pemilik, kedua akun dapat menerima akses kas bersama tanpa mengurangi kuota kas sendiri, dan setelah Premium berakhir batas Reguler kembali berlaku. Akses pencatatan Editor mengikuti ketersediaan pengelolaan kas oleh pemilik. Laporan, ekspor, serta utang/piutang tersedia untuk kedua akun.
- Form pengiriman bukti pembayaran mendukung JPG, JPEG, PNG, atau PDF dengan ukuran maksimal 3 MB.
- User dan admin menerima notifikasi dalam aplikasi saat pembayaran dikonfirmasi, disetujui, atau ditolak sesuai perannya.
- Pengguna dapat membuat order dengan memilih paket dan metode pembayaran yang tersedia serta memasukkan kode voucher.
- Riwayat order pengguna menampilkan order miliknya; admin dapat melihat seluruh order dan menyetujui atau menolak pembayaran yang menunggu verifikasi.
- Riwayat langganan ditampilkan sebagai daftar kartu dengan dua kartu per baris dan isi setiap kartu juga dua kolom mulai layar tablet; kedua grid menjadi satu kolom di ponsel, mendukung mode gelap. Setiap kartu memuat kode dan tanggal order, paket serta harga tanpa atribut durasi, total pembayaran, voucher (hanya jika terisi), diskon, detail pembayaran, status, masa aktif, dan catatan admin. Identitas user hanya ditampilkan bagi admin. Pencarian, filter status, pengurutan, paginasi, dan aksi pembayaran atau verifikasi tetap tersedia sesuai hak akses.
- Admin memiliki halaman pengelolaan paket langganan, rekening atau metode pembayaran manual, serta voucher dan kode uniknya.
- Form tambah dan edit voucher, metode pembayaran, serta paket langganan dibuka sebagai modal langsung dari halaman daftar masing-masing. Edit kode voucher juga menggunakan modal.
- Kode voucher dikelola melalui modal **Kelola kode** pada resource Voucher, tanpa halaman atau menu Kode Voucher terpisah. Form tambah kode tersedia langsung di atas tabel dengan tombol **Buat kode baru**, tanpa membuka modal tambahan. Form otomatis menggunakan voucher yang dipilih, menolak kode kosong atau duplikat, dan dikosongkan setelah berhasil disimpan. Edit kode tetap menggunakan modal. Jumlah kode pada daftar voucher diperbarui setelah penambahan atau penghapusan. Kode yang sudah dipakai pada order tidak dapat dihapus.

### 14. Dashboard pengguna

- Dashboard menjadi halaman utama pengguna biasa setelah login dan onboarding.
- Data kartu, lima transaksi terbaru, pengaturan tampilan, dan ringkasan admin menggunakan cache 30 menit yang dipisahkan per pengguna. Perubahan tabel model terkait membatalkan cache bagian yang bergantung padanya, termasuk query massal atau penulisan tanpa event model. Invalidasi berlaku untuk seluruh pengguna pada bagian terkait; data di dalam transaksi database dibaca langsung dan invalidasi diulang setelah commit. Status langganan diperbarui saat berganti tanggal.
- Dashboard menggunakan tab berikon bergaya Flowbite untuk Kas & Dompet, Utang & Piutang, Langganan, dan Transaksi. Tab tersusun dua kolom pada layar mobile tanpa perlu digeser ke samping, dan tetap satu baris pada layar lebar. Tab gabungan menampilkan kedua ringkasan berdampingan pada layar lebar dan bertumpuk pada layar sempit. Hanya panel tab aktif yang terlihat dengan lebar penuh. Tampilan panel dan padding vertikal tabel tetap padat; transaksi mobile tetap berupa ringkasan satu kolom.
- Menampilkan seluruh kas dan dompet milik pengguna beserta saldo masing-masing, termasuk yang akses pengelolaannya terbatas. Panel juga menampilkan total saldo dan jumlah kas atau dompet, dengan ikon pada tab dan dukungan tema gelap.
- Menampilkan total sisa utang dan piutang setelah pembayaran, masing-masing disertai tiga catatan dengan pembaruan terbaru.
- Menampilkan status langganan premium, tanggal akhir masa aktif, dan sisa hari; akun tanpa langganan atau kedaluwarsa ditampilkan sebagai Reguler.
- Tabel menampilkan lima transaksi terbaru milik pengguna dengan definisi kolom yang sama dengan daftar transaksi: tipe, tanggal dan pencatat (hanya kas dengan riwayat kolaborasi akun lain), kas dan dompet, kategori dan deskripsi, serta nominal.
- Melalui **Atur dashboard**, pengguna dapat menampilkan atau menyembunyikan setiap bagian individual serta mengubah urutannya dengan geser atau tombol urutan. Kas/dompet dan utang/piutang menjadi satu tab jika keduanya ditampilkan; tab gabungan tetap tersedia saat hanya salah satu bagiannya ditampilkan. Secara default, tab Transaksi muncul dan dibuka pertama; urutan yang telah diatur pengguna tetap dipakai.
- Baris transaksi yang tidak dapat dikelola menampilkan aksi informasi berwarna kuning, misalnya **Kas tidak aktif**, **Dompet tidak aktif**, atau **Transaksi audit saldo**. Klik penanda untuk membaca alasan dan langkah yang dapat dilakukan; aturan ubah/hapus tetap berlaku. Penanda tersedia di dashboard dan daftar transaksi.
- Baris tabel transaksi dashboard menyediakan aksi **Ubah** dan **Hapus** yang sama dengan daftar transaksi, sesuai hak pengelolaan pengguna. Aksi tersebut tidak tersedia untuk transaksi audit saldo.
- Bagian kas, dompet, utang, piutang, dan langganan menyediakan tombol **Kelola** yang membuka daftar resource masing-masing, termasuk ketika belum memiliki data. Tombol kas dan dompet berada pada tab **Kas & Dompet**; tombol utang dan piutang berada pada tab **Utang & Piutang**.
- Kartu transaksi menyediakan tombol rata kanan di atas tabel: **Tambah** untuk membuka modal pencatatan yang sama dengan daftar transaksi (mengikuti hak akses kas), dan **Lihat lengkap** untuk menuju daftar transaksi.
- Pilih tab untuk berpindah panel. Navigasi keyboard mendukung panah kiri/kanan serta Home/End, dengan penanda tab aktif dan relasi aksesibel antara tab dan panel. Perubahan data atau aksi Livewire mempertahankan tab aktif selama pengaturan urutan/visibilitas tidak berubah.
- Dashboard admin tetap hanya berisi statistik agregat administrasi.

Rangkuman ini dibuat berdasarkan implementasi yang tersedia di source code proyek pada 5 September 2026. Struktur dokumentasi dipisahkan menjadi backend dan frontend pada 12 September 2026.
