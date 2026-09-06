# Plan Fitur Tabungan Emas

## 1. Tujuan

Menambahkan dukungan tabungan berupa emas logam mulia di dalam kas, sehingga pengguna dapat:

- Mengalokasikan sebagian kekayaan pada suatu kas dalam bentuk emas.
- Mencatat pembelian, penjualan, dan koreksi jumlah emas.
- Melihat total berat serta nilai pasar emas terkini.
- Melihat saldo rupiah/non-emas dan total nilai kas setelah digabungkan dengan nilai emas.
- Mengetahui estimasi keuntungan atau kerugian berdasarkan modal perolehan.

## 2. Asumsi Produk

- Satu kas dapat memiliki nol atau beberapa tabungan emas.
- Emas merupakan aset di dalam kas, bukan jenis kas baru.
- Pembelian emas mengurangi saldo rupiah kas dan menambah kepemilikan emas.
- Penjualan emas mengurangi kepemilikan emas dan menambah saldo rupiah kas.
- Valuasi wajib menggunakan harga **buyback per gram**, karena lebih mendekati nilai yang dapat dicairkan pengguna.
- Satu kas dapat mempunyai beberapa tabungan emas untuk merek atau produk yang berbeda.
- Merek dan produk emas bersifat opsional.
- Perubahan harga emas bukan pemasukan atau pengeluaran dan tidak dimasukkan ke laporan arus kas.
- Berat emas disimpan dalam gram dengan presisi hingga empat angka desimal.
- Pemilik dan editor kas bersama dapat membeli dan menjual emas. Viewer hanya dapat melihat.
- Pembelian normal wajib terhubung ke dompet dan transaksi rupiah. Pencatatan tanpa transaksi rupiah hanya tersedia melalui aksi saldo awal atau koreksi yang diberi label secara jelas.
- Seluruh biaya perolehan, termasuk biaya cetak, premium pecahan, administrasi, dan biaya transaksi, dimasukkan ke total modal emas.
- Harga manual disimpan sebagai snapshot privat pada kas terkait, bukan sebagai harga global bagi seluruh pengguna.

## 3. Rumus Utama

```text
nilai_emas = total_berat_gram × harga_buyback_per_gram

total_nilai_kas = saldo_rupiah_kas + nilai_emas

estimasi_untung_rugi = nilai_emas - total_modal_emas
```

Harga pasar tidak disimpan sebagai bagian dari saldo kas. Nilai tersebut dihitung saat pengguna menjalankan aksi pengecekan, menggunakan harga terbaru atau cache terakhir yang tersedia.

## 4. Struktur Data

### 4.1. Tabel `tabungan_emas`

Menyimpan posisi emas terkini pada suatu kas.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `buku_kas_id` | foreign key | Kas tempat emas dialokasikan |
| `nama` | string | Contoh: Emas Antam |
| `merek` | string nullable | Antam, UBS, atau lainnya |
| `produk` | string nullable | Nama seri atau produk jika tersedia |
| `kadar` | decimal | Default 99,99% |
| `berat_gram` | decimal(12,4) | Total berat yang dimiliki |
| `total_modal` | bigint | Total modal kepemilikan aktif dalam rupiah |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu perubahan terakhir |

Relasi yang diperlukan:

- `BukuKas hasMany TabunganEmas`.
- `TabunganEmas belongsTo BukuKas`.
- `TabunganEmas hasMany TransaksiEmas`.

### 4.2. Tabel `transaksi_emas`

Menyimpan histori perubahan emas agar dapat diaudit.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `tabungan_emas_id` | foreign key | Tabungan emas terkait |
| `user_id` | foreign key | Pengguna yang melakukan aksi |
| `transaksi_id` | foreign key nullable | Transaksi rupiah pasangan |
| `jenis` | string/enum | `beli`, `jual`, `tambah`, atau `koreksi` |
| `tanggal` | datetime | Tanggal transaksi |
| `berat_gram` | decimal(12,4) | Perubahan berat emas |
| `harga_per_gram` | bigint nullable | Harga transaksi per gram |
| `biaya_tambahan` | bigint | Biaya cetak, premium, administrasi, dan biaya lain |
| `total_rupiah` | bigint nullable | Nilai transaksi rupiah |
| `catatan` | string nullable | Keterangan transaksi |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu perubahan terakhir |

### 4.3. Tabel `harga_emas`

Menyimpan snapshot harga agar aplikasi tetap dapat menampilkan harga terakhir ketika penyedia eksternal gagal.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `provider` | string | Nama penyedia data |
| `buku_kas_id` | foreign key nullable | Diisi untuk snapshot manual privat |
| `user_id` | foreign key nullable | Pengguna yang memasukkan harga manual |
| `sumber` | string | `api` atau `manual` |
| `jenis_harga` | string | Contoh: `buyback` |
| `harga_per_gram` | bigint | Harga rupiah per gram |
| `berlaku_pada` | datetime | Waktu harga dari provider |
| `diambil_pada` | datetime | Waktu aplikasi mengambil data |
| `metadata` | json nullable | Data sumber yang relevan |
| `created_at` | timestamp | Waktu penyimpanan |
| `updated_at` | timestamp | Waktu perubahan |

Snapshot API tidak harus dibuat pada setiap permintaan. Simpan hanya jika harga atau waktu sumber berubah. Snapshot API dapat digunakan secara global, sedangkan snapshot manual hanya dapat digunakan oleh kas terkait dan tidak boleh menjadi fallback global.

## 5. Service dan Integrasi Harga

### 5.1. Kontrak provider

Buat kontrak, misalnya `PenyediaHargaEmas`, yang menghasilkan data terstandardisasi:

```php
[
    'harga_buyback_per_gram' => 0,
    'berlaku_pada' => null,
    'provider' => '',
]
```

Buat `HargaEmasService` sebagai penghubung antara antarmuka, cache, snapshot database, dan provider eksternal. Dengan pendekatan ini, endpoint dapat diganti tanpa mengubah perhitungan bisnis maupun antarmuka.

### 5.2. Kandidat endpoint publik

Kandidat awal adalah [Logam Mulia API](https://github.com/iamutaki/logam-mulia-api), API publik gratis dan open-source yang menyediakan data harga jual/beli emas Indonesia dalam format JSON.

Base URL yang didokumentasikan saat plan ini dibuat:

```text
https://logam-mulia-api.iamutaki.workers.dev
```

Endpoint serta bentuk respons aktual harus diverifikasi kembali saat implementasi. Provider ini merupakan proyek komunitas dan mengambil data melalui scraping, sehingga tidak boleh menjadi satu-satunya sumber kebenaran.

Situs resmi [Logam Mulia ANTAM](https://www.logammulia.com/id/index) menampilkan harga emas, tetapi belum ditemukan API publik resmi yang terdokumentasi. Scraping langsung situs resmi tidak dijadikan pilihan utama karena struktur halamannya dapat berubah.

### 5.3. Ketahanan integrasi

- Simpan URL provider dalam konfigurasi dan environment.
- Gunakan HTTP timeout sekitar 5–10 detik.
- Terapkan retry terbatas hanya untuk kegagalan sementara.
- Validasi struktur, mata uang, jenis harga, nilai positif, dan waktu respons.
- Cache harga selama 1–6 jam agar tidak membebani layanan publik.
- Gunakan snapshot harga terakhir jika API gagal.
- Tampilkan waktu pembaruan serta sumber harga kepada pengguna.
- Beri label bahwa harga berasal dari cache jika bukan hasil permintaan terbaru.
- Sediakan input harga manual jika API gagal dan aplikasi belum mempunyai snapshot.
- Simpan harga manual sebagai snapshot privat pada kas terkait beserta pengguna dan waktu pencatatannya.
- Jangan gunakan snapshot manual suatu kas untuk kas atau pengguna lain.
- Jangan mengganti snapshot valid dengan respons kosong atau tidak valid.

## 6. Alur Pengguna

### 6.1. Membuat tabungan emas

Pada daftar kas, tambahkan aksi **Kelola tabungan emas**. Pemilik dapat membuat alokasi dengan mengisi:

- Nama tabungan emas.
- Merek emas.
- Produk emas.
- Kadar emas.
- Berat awal opsional.
- Total modal awal opsional.
- Catatan saldo awal.

Berat awal dicatat sebagai transaksi emas jenis `tambah` agar histori tidak dimulai tanpa jejak.

### 6.2. Membeli emas

Form pembelian berisi:

- Tabungan emas tujuan.
- Berat dalam gram.
- Harga beli per gram.
- Harga dasar emas yang dihitung dari berat dikali harga per gram.
- Biaya tambahan untuk biaya cetak, premium pecahan, administrasi, dan biaya transaksi.
- Total pembayaran yang dihitung dari harga dasar ditambah seluruh biaya tambahan.
- Tanggal.
- Dompet pembayaran.
- Kategori pengeluaran.
- Catatan.

Proses penyimpanan dilakukan dalam satu database transaction:

1. Validasi kepemilikan kas, tabungan emas, dompet, dan kategori.
2. Buat transaksi pengeluaran rupiah.
3. Tambahkan berat dan total modal emas; modal mencakup harga dasar dan seluruh biaya tambahan.
4. Buat histori transaksi emas.
5. Rollback seluruh perubahan jika salah satu langkah gagal.

### 6.3. Menjual emas

Form penjualan berisi:

- Tabungan emas sumber.
- Berat yang dijual.
- Harga jual per gram.
- Total penerimaan.
- Tanggal.
- Dompet penerima.
- Kategori pemasukan.
- Catatan.

Proses penyimpanan:

1. Pastikan berat yang dijual tidak melebihi kepemilikan.
2. Hitung pengurangan modal menggunakan metode rata-rata tertimbang yang sudah mencakup seluruh biaya perolehan.
3. Kurangi berat dan modal emas.
4. Buat transaksi pemasukan rupiah.
5. Buat histori transaksi emas.
6. Jalankan seluruh proses secara atomik.

### 6.4. Koreksi kepemilikan

Sediakan aksi koreksi untuk menyesuaikan hasil pemeriksaan fisik tanpa menghapus histori lama. Koreksi harus mencatat:

- Berat sebelum koreksi.
- Berat aktual.
- Selisih.
- Alasan koreksi.
- Pengguna dan waktu koreksi.

Koreksi tidak otomatis membuat transaksi rupiah. Aksi ini tidak boleh digunakan sebagai jalan pintas untuk pembelian normal dan wajib menyimpan alasan koreksi.

### 6.5. Mengecek nilai emas

Aksi **Cek nilai emas** hanya muncul pada kas yang mempunyai tabungan emas. Hasil pengecekan menampilkan:

- Total berat emas.
- Harga buyback per gram.
- Waktu terakhir pembaruan harga.
- Sumber harga.
- Status harga: terbaru, cache, atau manual.
- Nilai pasar emas.
- Total modal emas.
- Estimasi untung atau rugi belum terealisasi.
- Saldo rupiah/non-emas pada kas.
- Total nilai kas gabungan.

## 7. Perubahan Antarmuka

### 7.1. Daftar kas

Tambahkan informasi berikut pada `BukuKasResource`:

- Saldo rupiah.
- Nilai emas, jika tersedia.
- Total nilai kas.
- Aksi **Kelola tabungan emas**.
- Aksi **Cek nilai emas**.

Valuasi eksternal sebaiknya tidak dipanggil untuk setiap baris saat tabel dimuat. Gunakan snapshot/cache untuk tabel, sedangkan permintaan harga terbaru hanya dijalankan melalui aksi pengguna.

### 7.2. Ringkasan transaksi

Perbarui `KasOverview` ketika sebuah kas dipilih agar menampilkan kartu terpisah:

- Saldo rupiah kas.
- Estimasi nilai emas.
- Total nilai kas.

Jika semua kas dipilih, jumlahkan saldo rupiah dan nilai emas dari snapshot terakhir yang tersedia untuk masing-masing kas.

### 7.3. Halaman histori emas

Sediakan daftar histori dengan:

- Filter kas, tabungan emas, jenis transaksi, dan periode.
- Berat masuk/keluar.
- Harga serta total rupiah.
- Pengguna pencatat.
- Transaksi rupiah pasangan.
- Catatan.

## 8. Hak Akses

- Pemilik kas dapat membuat, membeli, menjual, mengoreksi, dan menghapus tabungan emas.
- Editor kas bersama dapat membeli dan menjual emas menggunakan dompet serta kategori miliknya sendiri, mengikuti pola transaksi bersama yang sudah berlaku.
- Editor tidak dapat membuat, mengoreksi, memindahkan, atau menghapus tabungan emas kecuali hak aksesnya diperluas pada iterasi berikutnya.
- Viewer hanya dapat melihat data dan valuasi emas.
- Data emas mengikuti pembatasan `UserScope` atau kebijakan kas yang sudah berlaku.
- Admin tidak memperoleh menu data keuangan pribadi, mengikuti kebijakan privasi aplikasi saat ini.
- Semua query mutasi harus memvalidasi kepemilikan secara eksplisit dan tidak hanya mengandalkan ID dari form.

Pembelian dan penjualan oleh editor wajib menyimpan identitas editor sebagai pencatat. Informasi dompet editor tetap disamarkan dari anggota lain sesuai aturan transaksi kas bersama yang sudah berlaku.

## 9. Dampak pada Fitur yang Sudah Ada

### 9.1. Laporan keuangan

- Laporan pemasukan dan pengeluaran tetap menggunakan transaksi rupiah.
- Pembelian emas tampil sebagai pengeluaran dan penjualan sebagai pemasukan.
- Perubahan harga pasar tidak dicatat sebagai transaksi.
- Ringkasan aset emas dapat ditambahkan sebagai bagian terpisah tanpa memengaruhi saldo awal/akhir laporan kas.

### 9.2. Penghapusan kas

- Kas yang masih mempunyai tabungan emas tidak boleh langsung dihapus.
- Pada aksi pindah dan hapus, pengguna harus memilih apakah seluruh tabungan emas ikut dipindahkan ke kas tujuan.
- Pemindahan hanya diizinkan ke kas milik pengguna yang sama.
- Transaksi rupiah, saldo rupiah, dan tabungan emas dipindahkan dalam satu database transaction.

### 9.3. Transfer kas dan dompet

- Transfer rupiah yang sudah ada tidak mengubah berat emas.
- Emas tidak dapat dipindahkan melalui aksi transfer saldo rupiah.
- Sediakan aksi pemindahan emas tersendiri jika dibutuhkan pada iterasi lanjutan.

### 9.4. Import transaksi

- Import transaksi yang ada tetap hanya mengimpor transaksi rupiah.
- Import pembelian/penjualan emas belum termasuk cakupan versi awal.

### 9.5. Audit saldo dompet

- Audit saldo dompet hanya merekonsiliasi rupiah.
- Emas tidak dihitung sebagai saldo dompet karena merupakan aset pada kas.

## 10. Tahapan Implementasi

### Tahap 1 — Fondasi data

- Tambahkan migration tabel tabungan emas, transaksi emas, dan snapshot harga.
- Tambahkan model, relasi, cast decimal/datetime, factory, dan policy.
- Tambahkan konfigurasi provider harga emas.

### Tahap 2 — Integrasi harga

- Buat kontrak provider.
- Implementasikan adapter endpoint publik.
- Tambahkan normalisasi serta validasi respons.
- Tambahkan cache, snapshot, timeout, retry, dan fallback.
- Tambahkan dukungan harga manual.

### Tahap 3 — Transaksi emas

- Buat service pembelian, penjualan, saldo awal, dan koreksi emas.
- Hubungkan pembelian/penjualan dengan `TransaksiService`.
- Pastikan locking serta database transaction mencegah saldo balapan atau perubahan parsial.

### Tahap 4 — Antarmuka

- Tambahkan pengelolaan tabungan emas dari halaman kas.
- Tambahkan form beli, jual, dan koreksi.
- Tambahkan histori emas.
- Tambahkan aksi pengecekan harga dan modal hasil valuasi.
- Perbarui kartu ringkasan kas.

### Tahap 5 — Integrasi fitur lama

- Sesuaikan penghapusan dan pemindahan kas.
- Pastikan laporan, import, transfer, audit dompet, dan kolaborasi tidak salah menghitung emas.
- Perbarui `FITUR_APLIKASI.md` sesuai implementasi final.

### Tahap 6 — Pengujian dan verifikasi

- Jalankan test terfilter untuk setiap perilaku yang diubah.
- Jalankan formatter pada file yang diubah.
- Verifikasi tampilan Filament untuk angka besar, desimal gram, harga cache, dan pesan kegagalan API.

## 11. Rencana Pengujian

Tambahkan pengujian terarah untuk perilaku berikut:

### Model dan data

- Relasi kas, tabungan emas, dan histori transaksi.
- Presisi berat emas tidak hilang.
- Posisi emas tidak dapat mempunyai berat atau modal negatif.

### Pembelian

- Pembelian mengurangi saldo kas dan dompet.
- Pembelian menambah berat serta total modal.
- Histori emas terhubung dengan transaksi rupiah.
- Seluruh perubahan di-rollback jika salah satu penyimpanan gagal.

### Penjualan

- Penjualan menambah saldo kas dan dompet.
- Penjualan mengurangi berat dan modal secara proporsional.
- Penjualan melebihi kepemilikan ditolak.
- Penjualan terakhir menyisakan berat dan modal nol tanpa residu pembulatan.

### Valuasi

- Nilai emas dihitung dari total gram dan harga buyback.
- Total nilai kas menggabungkan saldo rupiah dan nilai emas tepat satu kali.
- Untung/rugi dihitung dari nilai pasar dikurangi modal aktif.
- Nilai negatif pada saldo rupiah tetap diperhitungkan dengan benar.

### Provider harga

- Respons API valid disimpan dan digunakan.
- Respons tidak valid ditolak.
- Timeout menggunakan snapshot terakhir.
- Cache mencegah pemanggilan API berulang.
- Snapshot lama diberi status kedaluwarsa/cache.
- Harga manual digunakan jika API dan snapshot tidak tersedia.

### Otorisasi

- Pemilik dapat melakukan seluruh mutasi.
- Editor dapat membeli dan menjual, tetapi tidak dapat mengoreksi atau menghapus tabungan emas.
- Viewer tidak dapat melakukan mutasi.
- Pengguna lain tidak dapat membaca atau mengubah emas yang bukan haknya.
- Admin tidak melihat menu keuangan pribadi.

### Penghapusan kas

- Kas yang memiliki emas tidak dapat dihapus langsung.
- Pemindahan kas memindahkan aset emas secara atomik.
- Pemindahan ke kas milik pengguna lain ditolak.

Semua test lokal wajib menggunakan `--filter` yang menyasar perilaku terkait. Karena proyek menggunakan Pest 5, jalankan test dengan `--filter` dan `--tia`. Jika muncul pesan bahwa TIA tidak berlaku untuk partial run, ulangi dengan `--filter` dan `--parallel`.

## 12. Kriteria Penerimaan

Fitur dianggap selesai apabila:

- Pengguna dapat membuat tabungan emas pada salah satu kas miliknya.
- Pengguna dapat mencatat pembelian dan penjualan emas tanpa menyebabkan ketidaksesuaian saldo rupiah.
- Berat dan modal emas dapat ditelusuri melalui histori.
- Pengguna dapat meminta harga terbaru dan melihat sumber serta waktu pembaruannya.
- Aplikasi tetap dapat menampilkan snapshot terakhir ketika endpoint gagal.
- Pengguna dapat melihat saldo rupiah, nilai emas, dan total nilai kas gabungan.
- Perubahan harga emas tidak mengubah laporan arus kas.
- Hak akses kas bersama tetap terlindungi.
- Penghapusan atau pemindahan kas tidak meninggalkan data emas tanpa induk.
- Test terfilter untuk perhitungan, transaksi atomik, integrasi API, dan otorisasi berhasil.
- `FITUR_APLIKASI.md` telah diperbarui mengikuti implementasi akhir.

## 13. Keputusan Implementasi

- Valuasi wajib menggunakan harga buyback.
- Satu kas dapat mempunyai beberapa merek atau produk emas.
- Merek dan produk bersifat nullable.
- Biaya cetak, premium pecahan, administrasi, dan biaya transaksi dimasukkan ke total modal. Pemisahan `harga_dasar` dan `biaya_tambahan` tetap dipertahankan agar rincian biaya dapat diaudit.
- Editor kas bersama boleh membeli dan menjual emas menggunakan dompet serta kategori miliknya sendiri.
- Harga manual disimpan sebagai snapshot privat pada kas terkait. Snapshot tersebut dapat digunakan kembali sebagai fallback untuk kas yang sama, tetapi tidak dibagikan sebagai harga global kepada kas atau pengguna lain.
- Pembelian emas wajib terhubung ke dompet dan transaksi pengeluaran rupiah. Pengecualian hanya untuk saldo awal dan koreksi, yang dicatat melalui aksi khusus tanpa transaksi rupiah serta wajib memiliki alasan.
