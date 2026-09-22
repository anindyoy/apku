# Product Requirements Document — APKu

**Status:** Dokumentasi produk berdasarkan implementasi saat ini  
**Tanggal:** 21 September 2026  
**Produk:** APKu, aplikasi web pencatatan dan pemantauan keuangan pribadi  
**Acuan:** `FITUR_APLIKASI.md`, `README.md`, `resources/content/tutorial.json`, dan source code proyek

## 1. Ringkasan produk

APKu membantu pengguna mencatat aliran uang, melihat saldo dari sudut pandang kas dan dompet, memeriksa laporan, serta mengelola utang dan piutang. Pengguna dapat berbagi kas dengan anggota lain sesuai peran. Paket Premium menambah kapasitas kas dan dompet serta memungkinkan pemilik membuat kolaborasi kas. Pembayaran Premium diproses secara manual oleh admin.

Dokumen ini menggambarkan kebutuhan produk yang **sudah tercakup dalam aplikasi**, bukan janji untuk fitur mendatang. Target bisnis dan metrik pada bagian 10 adalah usulan yang masih perlu disepakati.

## 2. Masalah dan tujuan

### Masalah pengguna

- Catatan pemasukan dan pengeluaran tersebar sehingga saldo dan asal penggunaan uang sulit ditelusuri.
- Uang yang sama perlu dipantau menurut tujuan penggunaan (**kas**) dan tempat penyimpanan (**dompet**) tanpa dihitung ganda.
- Pengguna membutuhkan ringkasan periodik, rekonsiliasi saldo riil, dan riwayat utang atau piutang.
- Pengelolaan kas bersama memerlukan batasan akses dan jejak pencatat transaksi.

### Tujuan produk

1. Pengguna dapat menyelesaikan pengaturan awal dan mencatat transaksi pertama dengan alur yang jelas.
2. Saldo kas dan dompet tetap konsisten setelah transaksi, transfer, impor, audit, perubahan, atau penghapusan yang diizinkan.
3. Pengguna dapat menemukan transaksi dan memahami keadaan keuangannya melalui dashboard dan laporan.
4. Pemilik dapat berbagi kas tanpa membuka dompet atau data pribadi anggota lain.
5. Proses pembelian Premium dan pemeriksaan oleh admin memiliki status serta riwayat yang dapat dilacak.

## 3. Pengguna dan hak akses

| Peran | Kebutuhan utama | Batasan penting |
| --- | --- | --- |
| Reguler | Catat transaksi, impor, laporan, audit saldo, utang/piutang, tabungan emas | Maksimal dua kas milik sendiri dan dua dompet milik sendiri; tidak dapat membuat kolaborasi kas baru |
| Premium aktif | Seluruh kebutuhan Reguler, tambahan kas/dompet dan pembuatan kolaborasi | Akses Premium hanya berlaku selama masa aktif |
| Kolaborator Viewer | Membaca transaksi dan laporan kas yang dibagikan | Tidak dapat mengubah data kas |
| Kolaborator Editor | Mencatat transaksi pada kas bersama yang dapat dikelola | Hanya mengubah atau menghapus transaksi buatannya; memakai dompet dan aktivitas sendiri |
| Pengunjung link publik | Membaca ringkasan serta transaksi kas selama link aktif | Tanpa login; hanya baca; identitas pengguna dan nama dompet disembunyikan |
| Admin | Mengelola pengguna, langganan, metode pembayaran, voucher, dan pengaturan harga emas | Navigasi admin berfokus pada administrasi dan statistik agregat |

Kas yang diterima dari pengguna lain tidak mengurangi kuota kas milik kolaborator. Setelah Premium pemilik berakhir, pembuatan kolaborasi baru terhenti; kolaborasi yang ada masih dapat dikelola atau dicabut. Hak Editor mengikuti ketersediaan pengelolaan kas oleh pemilik.

## 4. Alur utama pengguna

1. **Mulai:** pengunjung mendaftar, memverifikasi email, masuk, lalu membuat kas utama, dompet utama, saldo awal, dan aktivitas melalui wizard.
2. **Catat uang:** pengguna memilih pemasukan, pengeluaran, transfer kas, atau transfer dompet; mengisi data yang relevan; menyimpan; lalu memeriksa saldo dan riwayat.
3. **Impor riwayat:** pengguna mengunduh template atau mengunggah CSV/XLSX, memetakan kolom, meninjau hasil validasi, memilih dampak saldo, lalu memantau status batch dan membatalkannya bila masih lengkap.
4. **Tinjau kondisi:** pengguna membuka dashboard, mencari transaksi, memfilter periode/kas/dompet, membaca laporan, dan mengekspor PDF atau XLSX.
5. **Rekonsiliasi:** pengguna memasukkan saldo riil tiap dompet; aplikasi mencatat snapshot audit dan transaksi penyesuaian jika ada selisih.
6. **Berbagi kas:** pemilik Premium aktif mengundang akun terverifikasi sebagai Viewer/Editor atau membuat link publik Viewer, menetapkan tanggal akses, lalu dapat mengubah atau mencabutnya.
7. **Berlangganan:** pengguna memilih paket dan metode pembayaran, memakai voucher bila tersedia, mengirim bukti, lalu menunggu keputusan admin. Persetujuan mengaktifkan atau memperpanjang Premium.

## 5. Kebutuhan fungsional

### FR-01 Akun dan pengaturan awal

- Sistem menyediakan registrasi, login, verifikasi email, pemulihan password, profil, perubahan password, dan notifikasi dalam aplikasi.
- Pengguna baru menjalani wizard kas utama, dompet utama, saldo awal, dan aktivitas umum sebelum memakai fitur utama.
- Registrasi dan login di production memakai Cloudflare Turnstile Managed dengan validasi server.
- **Kriteria penerimaan:** setelah wizard selesai, kas dan dompet utama tersedia dan saldo awal tercermin pada keduanya; pengguna dapat membuka dashboard.

### FR-02 Transaksi dan saldo

- Pengguna dapat membuat pemasukan, pengeluaran, transfer antar-kas, dan transfer antar-dompet melalui satu alur pencatatan.
- Pemasukan/pengeluaran menyimpan tanggal, kas, dompet, aktivitas, nominal, dan deskripsi. Setiap transaksi menyimpan identitas pencatat.
- Transfer memperbarui pasangan saldo secara atomik. Perubahan atau penghapusan transaksi yang diizinkan juga memperbarui saldo terkait.
- Daftar transaksi menyediakan filter periode, kas, dompet, navigasi periode, pencarian deskripsi, dan aksi sesuai hak akses. Pencarian global mencakup deskripsi, aktivitas, kas, dompet, jenis, nominal, dan pengguna.
- **Kriteria penerimaan:** saldo asal dan tujuan benar setelah transfer maupun perubahan transfer; pengguna tidak dapat mengubah transaksi pada kas terbatas atau transaksi audit saldo secara langsung.

### FR-03 Impor transaksi

- Sistem menerima CSV/XLSX untuk pemasukan dan pengeluaran, maksimal 10 MB dan 10.000 baris. Pengguna dapat mengunduh template XLSX.
- Sistem membaca header, menyarankan pemetaan kolom, memvalidasi baris, menampilkan pratinjau dan laporan error XLSX, serta dapat membuat aktivitas yang belum ada setelah konfirmasi.
- Opsi pembaruan saldo aktif secara default dan dapat dimatikan untuk impor riwayat. File yang sama tidak dapat diimpor dua kali selama batch sebelumnya masih berlaku.
- Hingga 1.000 baris diproses langsung; 1.001–10.000 baris melalui antrean privat. Riwayat menunjukkan status, progres, dan kegagalan. Batch lengkap dapat dibatalkan secara atomik.
- **Kriteria penerimaan:** satu kesalahan yang menggagalkan penyimpanan tidak meninggalkan sebagian transaksi; pembatalan menghapus transaksi batch dan memulihkan dampak saldo yang pernah diterapkan.

### FR-04 Kas, dompet, dan aktivitas

- Pengguna dapat membuat, mengubah, memilih default, serta menghapus kas/dompet sesuai kuota dan hak akses. Sebelum menghapus entitas berisi data atau saldo, sistem menyediakan pemindahan yang diperlukan.
- Aktivitas pemasukan dan pengeluaran dapat dikelola terpisah.
- Audit dompet menyimpan saldo aplikasi, saldo riil, selisih, tanggal, kas pencatatan, dan catatan. Selisih menghasilkan transaksi penyesuaian dengan aktivitas sistem **Audit Saldo**.
- **Kriteria penerimaan:** audit tanpa selisih tetap muncul di riwayat tanpa transaksi penyesuaian; audit dibatalkan bila saldo berubah selama proses.

### FR-05 Dashboard, pencarian, dan laporan

- Dashboard pengguna menampilkan kas, dompet, sisa utang/piutang, status Premium, dan lima transaksi terbaru. Urutan serta visibilitas bagian disimpan per akun.
- Laporan menyediakan periode harian, bulanan, tahunan, atau khusus; filter kas/dompet; saldo awal, pemasukan, pengeluaran, akumulasi, saldo akhir, rincian, dan persentase per aktivitas.
- Laporan dapat diekspor ke PDF lanskap dan XLSX.
- **Kriteria penerimaan:** tampilan dan ekspor mengikuti periode serta filter yang dipilih; informasi dompet anggota lain pada kas bersama tetap tersamarkan.

### FR-06 Utang dan piutang

- Pengguna dapat mencatat pihak terkait, nominal awal, tanggal, jatuh tempo opsional, penambahan nominal, dan pembayaran.
- Detail menampilkan riwayat serta saldo tersisa berjalan; daftar dapat dicari berdasarkan nama pihak.
- **Kriteria penerimaan:** status selesai/belum selesai mengikuti saldo tersisa setelah setiap perubahan riwayat.

### FR-07 Kolaborasi dan akses publik

- Hanya pemilik Premium aktif dapat membuat kolaborasi kas kepada akun terdaftar dan terverifikasi atau membuat link publik bertoken.
- Undangan privat memiliki peran Viewer/Editor serta tanggal mulai dan akhir opsional. Pemilik dapat mengubah peran dan mencabut akses.
- Link publik selalu Viewer; akses diperiksa terhadap jadwal dan status pencabutan pada setiap permintaan.
- **Kriteria penerimaan:** Viewer tidak melihat aksi tulis; Editor hanya mengubah transaksi buatannya; halaman publik tidak menampilkan identitas pengguna atau nama dompet.

### FR-08 Tabungan emas

- Pengguna dapat mencatat beberapa tabungan emas per kas dengan label, berat hingga empat desimal, tanggal pembelian, harga beli dan keterangan opsional.
- Sistem menampilkan estimasi nilai berdasar harga buyback publik, snapshot terakhir jika layanan gagal, atau harga manual privat untuk kas. Perubahan harga tidak membuat transaksi.
- Layanan internal pembelian/penjualan emas menjaga perubahan saldo rupiah dan emas secara atomik.
- **Kriteria penerimaan:** kas yang memiliki tabungan emas hanya dapat dihapus setelah emas dipindahkan ke kas lain milik pengguna yang sama.

### FR-09 Langganan Premium

- Pengguna dapat membandingkan paket, membuat lebih dari satu order aktif, memilih metode pembayaran manual, memasukkan voucher, mengunggah bukti JPG/JPEG/PNG/PDF maksimal 3 MB, dan melihat riwayat/status.
- Admin mengelola paket, metode pembayaran, voucher/kode, lalu menyetujui atau menolak pembayaran yang menunggu verifikasi.
- Snapshot paket, harga, metode pembayaran, diskon, dan total disimpan pada order. Persetujuan memperpanjang dari akhir masa aktif yang masih berlaku.
- **Kriteria penerimaan:** pemilik order dan admin saja yang dapat melihat bukti; penolakan menyimpan catatan; persetujuan memperbarui masa aktif tanpa menghilangkan sisa waktu.

### FR-10 Administrasi dan bantuan

- Dashboard admin menampilkan jumlah pengguna, Premium aktif, dan pembayaran menunggu verifikasi. Admin mengelola pengguna, tipe akun, masa aktif, serta pengaturan sumber harga emas.
- Landing page dan tutorial publik membantu pengunjung memahami produk. Tutorial memuat alur pengguna non-admin dan dapat dibuka dari halaman yang relevan.
- **Kriteria penerimaan:** menu keuangan pribadi tersembunyi dari navigasi admin; tutorial dapat dibuka tanpa login dan tautan kontekstual menuju topik yang sesuai.

## 6. Aturan bisnis lintas fitur

- Kas mengelompokkan tujuan uang; dompet menunjukkan tempat uang disimpan. Keduanya adalah dua pandangan atas uang yang sama dan totalnya tidak dijumlahkan sebagai kekayaan.
- Data pribadi dibatasi per pemilik. Akses kas bersama diberikan oleh peran, masa berlaku, dan kemampuan pemilik untuk mengelola kas tersebut.
- Akun Reguler dapat mengelola maksimal dua kas dan dua dompet milik sendiri. Premium aktif membuka tambahan tanpa batas kuota aplikasi yang dinyatakan dalam UI.
- Impor tanpa dampak saldo tetap menjadi riwayat transaksi dan tidak memengaruhi saldo ketika transaksi diubah, dihapus, atau batch dibatalkan.
- Transaksi audit saldo hanya dikoreksi melalui audit baru agar jejak rekonsiliasi terjaga.
- Nominal rupiah ditampilkan tanpa digit desimal; presisi berat emas tetap dipertahankan.
- Order langganan tidak kedaluwarsa otomatis; paket dan metode pembayaran yang tidak digunakan dinonaktifkan.

## 7. Kebutuhan nonfungsional

- **Keamanan dan privasi:** otorisasi diperiksa di server; bukti pembayaran dan berkas antrean impor disimpan privat; link publik memakai token acak dan pembatasan permintaan.
- **Integritas data:** operasi yang menyentuh beberapa saldo, baris impor, audit, dan keputusan langganan harus konsisten secara transaksional.
- **Ketersediaan data harga emas:** bila endpoint harga gagal, aplikasi dapat menggunakan snapshot terakhir atau harga manual yang relevan dan menampilkan sumber/waktu harga.
- **Responsivitas dan aksesibilitas:** alur utama mendukung ponsel, tablet, desktop, serta mode gelap; label aksi dan status tetap dapat dipahami tanpa mengandalkan warna saja.
- **Bahasa:** antarmuka utama dan tutorial memakai Bahasa Indonesia.
- **Operasional:** impor besar diproses lewat antrean; status kegagalan dapat dilihat pengguna; endpoint harga emas dipantau secara berkala.

## 8. Batas cakupan versi saat ini

- Impor hanya menerima pemasukan dan pengeluaran, belum transfer antar-kas atau antar-dompet.
- Transfer kas, transfer dompet, dan impor pada kas bersama hanya tersedia bagi pemilik kas.
- Halaman publik kas hanya menyediakan baca; tidak ada login atau aksi tulis melalui link tersebut.
- Pembayaran Premium menggunakan metode manual dan keputusan admin; tidak ada integrasi payment gateway yang dinyatakan pada implementasi.
- Perubahan harga emas tidak dicatat sebagai transaksi rupiah.

## 9. Dependensi dan risiko produk

| Area | Dependensi/risiko | Penanganan yang tersedia |
| --- | --- | --- |
| Registrasi | Layanan Turnstile di production | Validasi token pada form dan server |
| Impor besar | Worker antrean harus aktif | Status/progres/gagal pada riwayat; berkas privat dibersihkan setelah proses |
| Harga emas | Endpoint publik dapat gagal atau terlambat | Snapshot terakhir, harga manual, serta pemantauan endpoint |
| Premium | Pemeriksaan bukti bergantung pada admin | Status order, catatan keputusan, dan notifikasi dalam aplikasi |
| Kolaborasi | Link publik dapat tersebar | Token acak, masa akses, pencabutan, dan throttle |

## 10. Ukuran keberhasilan yang diusulkan

Metrik berikut **belum ditetapkan sebagai target resmi** dan memerlukan baseline serta keputusan pemilik produk:

- Persentase pendaftar yang menyelesaikan wizard dan membuat transaksi pertama dalam 24 jam.
- Persentase pengguna aktif yang mencatat transaksi pada sedikitnya dua minggu dalam satu bulan.
- Tingkat keberhasilan impor setelah pratinjau dan median waktu pemrosesan batch antrean.
- Persentase audit saldo yang selesai tanpa kegagalan konsistensi.
- Waktu median dari unggah bukti sampai keputusan admin dan tingkat order yang disetujui.
- Jumlah kas kolaboratif aktif, dibedakan menurut Viewer, Editor, dan link publik.

## 11. Keputusan produk yang masih terbuka

1. Target pengguna utama dan prioritas antara pencatatan pribadi, rumah tangga, dan kas bersama.
2. Target kuantitatif untuk aktivasi, retensi, performa impor, serta waktu pemeriksaan langganan.
3. Kebijakan penyimpanan/retensi bukti pembayaran, ekspor, log, dan data pengguna yang dihapus.
4. Apakah integrasi pembayaran otomatis, transfer pada kas bersama, atau impor transfer akan masuk roadmap berikutnya.

## 12. Sumber kebenaran dan pemeliharaan

Jika PRD ini berbeda dari perilaku aplikasi, source code dan test yang berlaku menjadi sumber kebenaran. Perubahan fitur pengguna perlu disertai pembaruan `FITUR_APLIKASI.md` dan `resources/content/tutorial.json` sesuai aturan proyek; PRD ini diperbarui ketika ruang lingkup atau keputusan produk berubah.
