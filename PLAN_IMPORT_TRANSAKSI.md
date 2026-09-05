# Rencana Implementasi Fitur Import Transaksi

## 1. Tujuan

Menambahkan fitur import transaksi melalui file CSV dan XLSX agar pengguna dapat mencatat banyak transaksi pemasukan dan pengeluaran sekaligus dengan tetap mengikuti validasi, hak akses, serta mekanisme pembaruan saldo yang sudah berlaku.

## 2. Cakupan MVP

- Mendukung file berformat `.csv` dan `.xlsx`.
- Mendukung transaksi dengan jenis `Pemasukan` dan `Pengeluaran`.
- Buku kas, dompet, dan kategori harus sudah tersedia pada akun pengguna.
- Transfer buku kas dan pemindahan saldo dompet belum didukung melalui import.
- Import bersifat atomik: jika satu baris tidak valid atau gagal disimpan, seluruh transaksi dalam batch dibatalkan.
- Import mengikuti batas pengelolaan buku kas dan dompet untuk akun reguler maupun Premium.
- Admin tidak dapat melakukan import karena menu transaksi pribadi memang tidak tersedia bagi admin.

## 3. Alur Pengguna

1. Pengguna membuka halaman **Transaksi**.
2. Pengguna memilih aksi **Import Transaksi**.
3. Pengguna dapat mengunduh template import.
4. Pengguna mengunggah file CSV atau XLSX yang telah diisi.
5. Sistem membaca dan memvalidasi seluruh baris.
6. Sistem menampilkan pratinjau yang memuat:
   - nama file;
   - jumlah seluruh baris;
   - jumlah baris valid;
   - jumlah baris bermasalah;
   - nomor baris dan pesan kesalahan;
   - total pemasukan dan pengeluaran;
   - estimasi perubahan saldo.
7. Tombol konfirmasi hanya tersedia jika semua baris valid.
8. Setelah dikonfirmasi, sistem menyimpan seluruh transaksi dalam satu database transaction.
9. Sistem menampilkan notifikasi dan ringkasan hasil import.

## 4. Format Template

| Kolom | Wajib | Ketentuan |
| --- | --- | --- |
| `tanggal` | Ya | Format `YYYY-MM-DD HH:mm` dan tidak melebihi waktu saat import |
| `jenis` | Ya | Hanya `Pemasukan` atau `Pengeluaran` |
| `buku_kas` | Ya | Nama buku kas milik pengguna yang dapat dikelola |
| `dompet` | Ya | Nama dompet aktif milik pengguna yang dapat dikelola |
| `kategori` | Ya | Nama kategori milik pengguna dengan tipe yang sesuai dengan `jenis` |
| `nominal` | Ya | Bilangan bulat lebih dari nol, tanpa simbol mata uang |
| `deskripsi` | Tidak | Teks bebas |

Template perlu menyertakan satu baris contoh dan petunjuk singkat. Header harus tetap agar proses import konsisten dan pesan validasi dapat dibuat jelas.

## 5. Aturan Normalisasi dan Validasi

### 5.1 Validasi file

- Ekstensi dan MIME type harus sesuai CSV atau XLSX.
- Terapkan batas ukuran file yang wajar, misalnya 3 MB.
- Terapkan batas jumlah baris untuk MVP, misalnya 1.000 transaksi per file.
- File kosong atau file tanpa baris data ditolak.
- Header wajib harus tersedia dan tidak boleh duplikat.
- Baris kosong diabaikan.

### 5.2 Normalisasi nilai

- Nama header dinormalisasi menjadi huruf kecil dan spasi diganti garis bawah.
- Spasi di awal dan akhir nilai teks dihapus.
- Pencarian nama buku kas, dompet, dan kategori dilakukan secara case-insensitive.
- Nominal hanya menerima bilangan bulat positif. Pemisah ribuan yang didukung harus ditentukan secara eksplisit agar tidak menimbulkan salah tafsir.
- Tanggal dikonversi ke zona waktu aplikasi sebelum disimpan.

### 5.3 Validasi per baris

- Jenis transaksi termasuk `Pemasukan` atau `Pengeluaran`.
- Tanggal valid dan tidak berada di masa depan.
- Nominal lebih dari nol.
- Buku kas merupakan milik pengguna dan dapat dikelola sesuai status akun.
- Dompet merupakan milik pengguna, belum dihapus, dan dapat dikelola sesuai status akun.
- Kategori merupakan milik pengguna dan tipenya sama dengan jenis transaksi.
- Nama entitas yang tidak ditemukan atau ambigu menghasilkan error dan tidak dipilih otomatis.
- Pesan kesalahan menyertakan nomor baris, nama kolom, dan alasan kegagalan.

## 6. Rancangan Teknis

### 6.1 Antarmuka Filament

- Tambahkan header action **Import Transaksi** pada `ListTransaksis`.
- Tambahkan aksi **Unduh Template** di dalam modal import.
- Gunakan upload file privat/sementara.
- Setelah file diunggah, tampilkan tahap pratinjau sebelum konfirmasi.
- Tombol import dinonaktifkan ketika masih terdapat error.
- Setelah proses selesai, bersihkan file sementara dan segarkan tabel serta widget saldo.

### 6.2 Service import

Buat service khusus, misalnya `ImportTransaksiService`, dengan tanggung jawab:

- membaca CSV dan XLSX;
- memvalidasi header;
- menormalisasi data mentah;
- mengambil dan memetakan buku kas, dompet, serta kategori milik pengguna;
- menghasilkan hasil pratinjau beserta daftar error;
- menyimpan batch yang telah tervalidasi;
- mengembalikan ringkasan hasil import.

Pisahkan proses membaca/validasi dari proses penyimpanan agar pratinjau tidak mengubah database.

### 6.3 Penyimpanan transaksi

- Jalankan penyimpanan batch dalam `DB::transaction()`.
- Gunakan `TransaksiService::buat()` untuk setiap baris agar aturan otorisasi dan pembaruan saldo tetap memiliki satu sumber kebenaran.
- Kunci buku kas dan dompet melalui mekanisme yang sudah tersedia pada service transaksi.
- Jika satu transaksi gagal dibuat, lempar exception dan rollback seluruh batch.
- Jangan membuat transaksi secara langsung menggunakan model karena dapat melewati aturan saldo dan hak akses.

### 6.4 Pencegahan import ganda

- Hitung hash berdasarkan isi file dan pengguna.
- Simpan metadata batch import yang berhasil, minimal:
  - `user_id`;
  - nama file asli;
  - hash file;
  - jumlah baris;
  - waktu import;
  - status.
- Jika hash yang sama pernah berhasil diimpor oleh pengguna tersebut, tampilkan peringatan dan blokir import ulang pada MVP.
- Jangan menyimpan isi transaksi mentah pada log aplikasi.

Kebutuhan ini kemungkinan memerlukan tabel baru, misalnya `import_transaksi`, beserta relasi opsional dari transaksi ke batch import jika audit per transaksi diperlukan.

## 7. Penanganan Error

- Error file ditampilkan pada bagian atas modal.
- Error per baris ditampilkan dalam tabel yang dapat digulir.
- Batasi jumlah error yang ditampilkan sekaligus, tetapi sediakan total error sebenarnya.
- Kesalahan tidak boleh menampilkan stack trace atau informasi internal kepada pengguna.
- Kegagalan penyimpanan menghasilkan notifikasi bahwa seluruh batch dibatalkan.
- File sementara dihapus setelah berhasil, gagal, atau melewati masa kedaluwarsa.

## 8. Keamanan dan Hak Akses

- Semua resolusi entitas harus dibatasi berdasarkan `user_id`, bukan hanya mengandalkan nilai dari form.
- Validasi ulang hak akses dilakukan saat konfirmasi, bukan hanya saat pratinjau.
- Gunakan aturan `dapatMengelolaTransaksiPada()` dan `dapatMengelolaTransaksiPadaDompet()` yang telah tersedia.
- Dompet yang sudah dihapus tidak boleh menjadi tujuan import.
- Jangan menerima ID model langsung dari isi file pada MVP.
- Batasi ukuran, jumlah baris, dan tipe file untuk mencegah penyalahgunaan sumber daya.

## 9. Pengujian Terprogram

Buat test khusus, misalnya `tests/Feature/Filament/ImportTransaksiTest.php`, dengan cakupan:

- CSV valid berhasil diimpor.
- XLSX valid berhasil diimpor.
- Pemasukan menambah saldo buku kas dan dompet.
- Pengeluaran mengurangi saldo buku kas dan dompet.
- Beberapa baris valid diproses dalam satu batch.
- Header wajib yang hilang atau salah ditolak.
- File kosong, ekstensi salah, terlalu besar, atau melewati batas baris ditolak.
- Jenis transaksi yang tidak didukung ditolak.
- Tanggal tidak valid atau berada di masa depan ditolak.
- Nominal kosong, nol, negatif, atau bukan angka ditolak.
- Buku kas, dompet, dan kategori yang tidak ditemukan ditolak.
- Entitas milik pengguna lain ditolak.
- Kategori dengan tipe yang tidak sesuai ditolak.
- Dompet yang telah dihapus ditolak.
- Buku kas atau dompet yang dibatasi oleh masa aktif tidak dapat digunakan.
- Satu baris tidak valid menyebabkan seluruh batch tidak disimpan.
- Kegagalan saat penyimpanan menyebabkan rollback transaksi dan saldo.
- File yang sama tidak dapat diimpor dua kali.
- Aksi import tidak tersedia bagi admin.
- Pratinjau tidak mengubah database maupun saldo.

Hindari penggunaan `assertSee` sesuai aturan proyek. Gunakan assertion Livewire/Filament yang spesifik, pemeriksaan state komponen, dan assertion database.

Karena proyek menggunakan Pest 5, jalankan test lokal secara terarah:

```powershell
php artisan test --filter=import_transaksi --tia
```

Jika muncul pesan bahwa TIA tidak berlaku untuk partial run, ulangi dan gunakan:

```powershell
php artisan test --filter=import_transaksi --parallel
```

Jangan menjalankan seluruh test suite di mesin lokal.

## 10. Dokumentasi

Setelah implementasi selesai, perbarui `FITUR_APLIKASI.md` pada bagian pengelolaan transaksi dengan informasi bahwa:

- pengguna dapat mengimpor pemasukan dan pengeluaran melalui CSV/XLSX;
- sistem menyediakan template dan pratinjau validasi;
- import mengikuti hak akses buku kas dan dompet;
- seluruh batch dibatalkan jika terdapat data yang tidak valid;
- transfer belum didukung melalui import, jika batasan tersebut masih berlaku.

## 11. Urutan Implementasi

- [x] Tetapkan format final template, batas ukuran file, dan batas jumlah baris.
- [x] Gunakan OpenSpout yang sudah tersedia sebagai dependensi Filament untuk membaca dan menulis XLSX.
- [x] Buat template XLSX; CSV menggunakan header dan susunan kolom yang sama.
- [x] Buat struktur metadata batch import dan migration untuk pencegahan duplikasi.
- [x] Buat parser dan normalisasi data.
- [x] Buat validator file dan validator per baris.
- [x] Buat proses pratinjau tanpa perubahan database.
- [x] Buat penyimpanan atomik melalui `TransaksiService`.
- [x] Tambahkan aksi dan modal import pada halaman transaksi.
- [x] Tambahkan penanganan file sementara dan pesan error.
- [x] Tambahkan proteksi import ganda.
- [x] Tambahkan test terarah untuk perilaku utama.
- [x] Jalankan test import menggunakan filter yang diwajibkan.
- [x] Perbarui `FITUR_APLIKASI.md`.

## 12. Kriteria Penerimaan

Fitur dianggap selesai ketika:

- pengguna dapat mengunduh template dan mengunggah CSV/XLSX;
- seluruh baris divalidasi sebelum database berubah;
- error menunjukkan lokasi dan alasan yang dapat dipahami;
- file valid menghasilkan transaksi dengan saldo buku kas dan dompet yang benar;
- satu kegagalan membatalkan seluruh batch;
- pengguna tidak dapat mengimpor ke entitas milik pengguna lain atau entitas yang tidak dapat dikelola;
- import file yang sama tidak membuat transaksi ganda;
- test terarah untuk seluruh perilaku utama lulus;
- dokumentasi fitur sesuai dengan implementasi.

## 13. Pengembangan Lanjutan

Di luar MVP, fitur dapat dikembangkan untuk mendukung:

- pemetaan kolom secara fleksibel;
- pembuatan kategori otomatis dengan konfirmasi;
- import sebagian untuk baris valid;
- unduhan laporan error;
- pemrosesan melalui antrean untuk file besar;
- status dan riwayat batch import;
- pembatalan batch import dengan pembalikan saldo;
- dukungan transfer buku kas dan pemindahan saldo dompet.

### Progres Pengembangan Lanjutan

- [x] Pemetaan kolom fleksibel dengan pembacaan header dan saran otomatis untuk nama kolom umum.
- [x] Pembuatan kategori otomatis dengan konfirmasi, pratinjau kategori baru, dan rollback atomik.
- [ ] Import sebagian untuk baris valid.
- [x] Unduhan laporan error XLSX berisi data asli dan alasan kegagalan per baris.
- [ ] Pemrosesan melalui antrean untuk file besar.
- [x] Status dan riwayat batch import.
- [x] Pembatalan batch import dengan pembalikan saldo.
- [ ] Dukungan transfer buku kas dan pemindahan saldo dompet.
