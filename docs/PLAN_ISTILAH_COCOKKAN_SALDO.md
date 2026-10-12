# Plan: Mengganti Istilah "Audit Saldo" Menjadi "Cocokkan Saldo"

Dokumen ini adalah rencana kerja untuk mengganti istilah **Audit Saldo** pada seluruh teks yang terlihat pengguna, agar lebih mudah dipahami oleh pengguna awam.

> Catatan: rencana ini disusun dari `FITUR_APLIKASI.md`, `AGENTS.md`, dan `welcome.blade.php`. Lokasi kode di bagian "Cakupan perubahan" harus ditelusuri dengan pencarian pada source code sebelum dikerjakan. Source code dan test yang berlaku tetap menjadi sumber kebenaran.

## 1. Latar belakang

Fitur yang sekarang bernama **Audit Saldo** bekerja seperti ini: pengguna memeriksa saldo nyata di luar aplikasi (menghitung uang tunai atau melihat mutasi bank/e-wallet), aplikasi membandingkannya dengan saldo catatan, lalu selisihnya dicatat sebagai transaksi penyesuaian pada kas yang dipilih.

Kata "audit" bermasalah untuk pengguna awam:

- Dalam akuntansi, audit berarti pemeriksaan formal oleh pihak independen. Terdengar berat dan menghakimi untuk pengguna pribadi.
- Kata ini tidak memberi tahu bahwa aksinya mengubah data (membuat transaksi penyesuaian).
- Label **Audit Saldo** pada transaksi di daftar, laporan, dan ekspor tidak menjelaskan bahwa transaksi itu adalah koreksi.

Aktivitas yang sebenarnya dilakukan adalah **pencocokan** (rekonsiliasi) saldo. Landing page sudah memakai frasa "cocokkan saldo", sehingga penyeragaman istilah juga memperbaiki konsistensi.

## 2. Keputusan istilah (sudah disepakati)

Target pengguna adalah orang awam, sehingga dipilih bahasa sehari-hari. Aktivitas dan transaksi hasilnya dibedakan: yang pertama dinamai menurut tindakan pengguna, yang kedua menurut dampaknya pada saldo.

| Sekarang | Menjadi |
|---|---|
| Audit Saldo (nama fitur, tombol di kartu Sumber Dana) | **Cocokkan Saldo** |
| Tambah audit | Tambah pencocokan |
| Riwayat audit, daftar Audit Saldo | **Riwayat pencocokan** |
| Tombol audit massal | Pencocokan massal |
| Catatan audit, rincian audit | Catatan pencocokan, rincian pencocokan |
| mengaudit saldo | mencocokkan saldo |
| Label transaksi **Audit Saldo** | **Penyesuaian Saldo** |
| Penanda kuning "Transaksi audit saldo" | "Transaksi penyesuaian saldo" |
| "jejak rekonsiliasi" | "jejak pencocokan" |
| "Audit saldo" pada tabel perbandingan Reguler dan Premium | "Cocokkan saldo" |

Teks tombol pada kartu Sumber Dana adalah **Cocokkan saldo** (sudah diputuskan).

Istilah **Saldo riil**, **Selisih**, dan **Sumber Dana** tidak diubah.

Kapitalisasi mengikuti konteks kalimat (judul dan tombol memakai huruf kapital awal kata seperti sekarang; kalimat biasa memakai huruf kecil), serupa aturan istilah Sumber Dana di `AGENTS.md`.

## 3. Batasan: yang tidak diubah

Perubahan utama menyentuh **teks yang tampil ke pengguna**, ditambah slug URL dan nama route (lihat butir 4.7). Hal berikut tetap dipertahankan karena tidak tampil sebagai label dan mengubahnya berisiko tanpa manfaat bagi pengguna:

- Nama tabel: `audit_saldo_dompet`, `audit_saldo_dompet_detail`.
- Nama kolom dan kunci: `audit_saldo_dompet_id`, `audit_saldo_dompet_detail_id` pada `transaksi`.
- Nama class, model, relasi, enum, dan method internal yang memuat kata audit.
- Kunci cache dan kunci session lama.

Slug URL dan nama route **diganti** ke istilah baru dengan redirect permanen dari slug lama (butir 4.7). Itu satu-satunya perubahan di luar teks tampilan.

Karena tidak ada migrasi dan tidak ada perubahan skema, bagian **Skema database** di `FITUR_APLIKASI.md` hanya perlu keterangan bahwa nama tabel lama dipertahankan.

## 4. Cakupan perubahan

Telusuri kata `audit` (tanpa memperhatikan huruf besar/kecil) pada `app`, `resources/views`, `resources/content`, `lang`, `database/seeders`, dan `tests`. Kelompokkan hasilnya menurut area berikut.

### 4.1 Landing page (`resources/views/welcome.blade.php`)

- Kartu fitur 02 (**Kas & sumber dana**): "cocokkan saldo melalui audit sumber dana" menjadi "cocokkan saldo dengan kondisi uang sebenarnya".
- Daftar akun Reguler: "Audit saldo, emas, utang dan piutang" menjadi "Cocokkan saldo, emas, utang dan piutang".

### 4.2 UI aplikasi

- Label resource atau halaman, judul halaman, dan breadcrumb.
- Judul modal dan form (kas pencatatan, tanggal, catatan, penghitung uang, tombol **Ulangi**, toggle **Uang Logam** hanya diperiksa konteks katanya).
- Tombol **Audit saldo** pada kartu Sumber Dana menjadi **Cocokkan saldo**. Tombol audit massal pada header halaman Sumber Dana menjadi **Pencocokan massal**, sedangkan tombol **Riwayat audit** dan **Tambah audit** pada daftar riwayat menjadi **Riwayat pencocokan** dan **Tambah pencocokan**.
- Label **Audit Saldo** pada transaksi di dashboard, daftar transaksi, laporan, dan ekspor PDF/Excel menjadi **Penyesuaian Saldo**. Pastikan konstanta atau enum yang menghasilkan label ekspor ikut diperbarui sehingga hasil ekspor konsisten dengan tampilan.
- Aksi informasi kuning **Transaksi audit saldo** menjadi **Transaksi penyesuaian saldo**, termasuk teks alasan dan langkah yang dapat dilakukan saat penanda diklik.
- Notifikasi, pesan validasi, helper text, placeholder, dan empty state yang menyebut audit.
- Tabel perbandingan Reguler dan Premium di halaman Langganan (baris dari 9 fitur).

### 4.3 Data seed dan factory

- `description` seed atau factory yang memuat teks audit dan tampil pada kolom Deskripsi transaksi. Perhatikan juga catatan atau deskripsi yang tersimpan di database pada transaksi penyesuaian lama (lihat butir 6).

### 4.4 Tutorial (`resources/content/tutorial.json`)

- Perbarui judul topik, langkah, nama menu atau tombol, contoh, dan catatan yang menyebut audit.
- Pertahankan id topik yang sudah ada agar tautan ke topik tidak patah; ubah hanya judul dan isi yang dibaca pengguna.
- Pada topik tentang Cocokkan Saldo, sebut padanan sekali saja, misalnya "Cocokkan Saldo (dikenal juga sebagai rekonsiliasi)", lalu gunakan **Cocokkan Saldo** untuk seterusnya.
- Tetap gunakan istilah **Sumber Dana**, bukan Dompet.
- Pertahankan struktur JSON. Jika jumlah topik atau strukturnya berubah, sesuaikan test tutorial dan keterangan jumlah topik di `FITUR_APLIKASI.md`.

### 4.5 `FITUR_APLIKASI.md`

Sesuaikan bagian berikut (nomor baris menurut versi saat ini, mungkin bergeser):

- Alur pembuatan dan perilaku audit (sekitar baris 88-95), termasuk kalimat "jejak rekonsiliasi".
- Hak akses dan form (sekitar baris 249-254).
- Navigasi Setting dan pengecualian akses (sekitar baris 313).
- Format nominal yang menyebut audit saldo (sekitar baris 318).
- Tabel perbandingan Reguler dan Premium (sekitar baris 328).
- Penanda transaksi yang tidak dapat dikelola (sekitar baris 349-350).
- Bagian **Skema database**: tambahkan keterangan bahwa nama tabel dan kolom lama dipertahankan demi kompatibilitas dan tidak mengikuti istilah tampilan.
- Ringkasan istilah: tambahkan satu kalimat bahwa nama tampilan fitur adalah **Cocokkan Saldo** dan label transaksinya **Penyesuaian Saldo**.

### 4.6 Aturan proyek (`AGENTS.md`)

Tambahkan bagian baru serupa **Istilah Sumber Dana**, misalnya **Istilah Cocokkan Saldo**:

- Teks yang terlihat pengguna memakai **Cocokkan Saldo** (fitur) dan **Penyesuaian Saldo** (label transaksi), bukan "audit".
- Pengecualian teknis: nama tabel, kolom, kunci, class, route/slug lama, dan kunci cache.
- Sebelum menyelesaikan pekerjaan yang menyentuh istilah ini, telusuri `audit` pada `app`, `resources/views`, `resources/content`, `lang`, dan `database/seeders`, lalu pastikan sisa yang tampil ke pengguna hanya pengecualian di atas.

### 4.7 Slug URL dan nama route

Sudah diputuskan: slug dan nama route diganti ke istilah baru, dengan redirect permanen (HTTP 301) dari slug lama. Aplikasi belum digunakan di production, sehingga risiko tautan lama milik pengguna kecil, tetapi redirect tetap dipasang agar bookmark pengembang atau staging tidak patah dan perilakunya konsisten bila nanti ada tautan yang sudah tersebar.

- Usulan slug (sesuaikan dengan slug yang berlaku sekarang): halaman utama pencocokan `cocokkan-saldo`, daftar riwayat `riwayat-pencocokan`.
- Ganti slug pada Resource atau Page terkait (misalnya properti `$slug` atau pendaftaran route halaman). Nama route Filament diturunkan dari slug, sehingga seluruh pemanggilan `route(...)`, `getUrl()`, redirect internal, tautan di Blade, dan test yang memakai nama route atau URL lama harus ikut diperbarui. Telusuri nama route lama di seluruh kode dan test.
- Tambahkan redirect permanen dari slug lama ke slug baru, termasuk URL turunan (misalnya halaman buat atau lihat). Contoh bentuk (komentar Bahasa Indonesia):

```php
// Redirect permanen dari slug lama agar tautan atau bookmark lama tetap berfungsi.
Route::permanentRedirect('/admin/audit-saldo-dompet', '/admin/riwayat-pencocokan');
```

- Pastikan redirect tidak melewati middleware yang membuat tamu terlempar ke login tanpa kembali ke tujuan baru. Bila redirect diletakkan di luar grup Filament, uji perilaku untuk pengguna login maupun tamu.
- Nama class Resource, Page, dan Model tidak diganti. Label navigasi dan judul memakai istilah baru melalui properti label bawaan Filament.

## 5. Pengujian

Semua test dijalankan lokal dengan `--filter` dan `--parallel`, contoh `php artisan test --filter="PencocokanSaldo" --parallel`. Jangan memakai `assertSee`; gunakan assertion terhadap label atau properti yang dihasilkan (misalnya nilai method label, konfigurasi aksi Filament, atau isi respons yang dicek lewat assertion lain).

1. Sesuaikan ekspektasi label pada test yang saat ini memeriksa teks audit (UI, label transaksi, aksi informasi, tabel perbandingan, notifikasi).
2. Test label transaksi: transaksi penyesuaian menghasilkan label **Penyesuaian Saldo** pada daftar, dashboard, laporan, dan ekspor.
3. Test landing page: dua teks yang diperbarui pada `welcome.blade.php`.
4. Test tutorial: topik yang diperbarui masih valid terhadap struktur JSON dan tidak lagi memuat kata audit pada teks yang dibaca pengguna. Kata "rekonsiliasi" hanya muncul satu kali pada topik tersebut sebagai padanan.
5. Test tabel perbandingan Reguler dan Premium: jumlah baris tetap 9 dan baris terkait memakai istilah baru.
6. Test regresi perilaku: audit tetap menyimpan snapshot, selisih positif dicatat sebagai pemasukan dan negatif sebagai pengeluaran tanpa kategori, serta transaksi penyesuaian tetap tidak bisa diubah atau dihapus langsung.
7. Test redirect: URL lama (halaman utama pencocokan dan daftar riwayat, serta URL turunannya) menghasilkan redirect permanen (301) ke slug baru; URL baru dapat diakses oleh pengguna yang berhak dan tetap menolak yang tidak berhak.
8. Test nama route: seluruh tautan internal (tombol kartu Sumber Dana, tombol header, tombol riwayat, penanda transaksi penyesuaian) mengarah ke route baru, tidak ada yang memakai nama route lama.
9. Pemeriksaan sisa kata: pencarian `audit` pada direktori di butir 4 hanya menyisakan pengecualian teknis pada butir 3.

## 6. Data lama di database

- Label **Audit Saldo** pada transaksi lama diturunkan dari relasi ke audit, bukan teks yang tersimpan, sehingga otomatis ikut berubah setelah kode diganti. Verifikasi bahwa tidak ada kolom `deskripsi` atau `catatan` yang menyimpan kata "Audit Saldo" sebagai teks statis.
- Karena aplikasi belum digunakan di production, tidak ada data pengguna nyata yang perlu dimigrasi. Jika ada teks statis di seeder atau factory, cukup perbarui sumbernya lalu bangun ulang data lokal atau staging (`php artisan migrate:fresh --seed`). Tidak diperlukan command migrasi data.

## 7. Urutan pengerjaan

1. Telusuri kata `audit` di seluruh area butir 4, buat daftar temuan dan klasifikasikan: tampil ke pengguna atau pengecualian teknis.
2. Ganti teks UI dan label transaksi (termasuk ekspor dan laporan).
3. Ganti slug dan nama route, perbarui semua pemanggilnya, lalu pasang redirect permanen dari slug lama (butir 4.7).
4. Ganti teks landing page dan tabel perbandingan Reguler dan Premium.
5. Perbarui `tutorial.json`.
6. Perbarui seed dan factory yang relevan.
7. Perbarui test (butir 5) dan jalankan dengan filter terarah.
8. Perbarui `FITUR_APLIKASI.md` dan tambahkan aturan baru di `AGENTS.md`.
9. Uji manual di `apku.test`: alur Cocokkan Saldo dari kartu Sumber Dana dan dari header, riwayat, label transaksi di daftar dan laporan, ekspor PDF/Excel, penanda kuning pada transaksi penyesuaian, tampilan landing page, serta akses URL lama yang harus diarahkan ke URL baru.

## 8. Keputusan

Sudah diputuskan:

1. **Slug URL dan nama route**: diganti ke istilah baru dengan redirect permanen dari slug lama. Aplikasi belum digunakan di production.
2. **Teks tombol pada kartu Sumber Dana**: **Cocokkan saldo**.
3. **Istilah "rekonsiliasi" pada penjelasan**: boleh muncul satu kali di tutorial sebagai padanan ("dikenal juga sebagai rekonsiliasi") untuk pengguna yang akrab istilah akuntansi. Tidak dipakai sebagai nama fitur, tombol, atau label di bagian lain.

Tidak ada keputusan yang masih terbuka.

## 9. Risiko

| Risiko | Mitigasi |
|---|---|
| Ada teks audit yang terlewat pada UI, ekspor, atau notifikasi | Pencarian kata `audit` sebagai pemeriksaan wajib di akhir (butir 5.9). |
| Label ekspor dan tampilan tidak sinkron | Sumber label dipusatkan; test label mencakup daftar, laporan, dan ekspor. |
| Tautan atau bookmark lama patah, atau nama route lama tertinggal di kode | Redirect permanen dari slug lama; telusuri nama route lama di kode dan test; test redirect dan test nama route. |
| Kata audit muncul kembali pada perubahan berikutnya | Aturan baru di `AGENTS.md` (butir 4.6). |
| Teks statis lama tersimpan di database | Verifikasi pada butir 6 sebelum dianggap selesai. |

## 10. Kriteria selesai

- Tidak ada teks yang terlihat pengguna yang masih memakai kata "audit", kecuali pengecualian teknis pada butir 3.
- Landing page, UI, laporan, ekspor, tutorial, dan tabel perbandingan memakai istilah **Cocokkan Saldo** dan **Penyesuaian Saldo** secara konsisten.
- Perilaku fitur tidak berubah (selain slug URL dan nama route yang diganti dengan redirect permanen dari slug lama); tidak ada migrasi skema.
- Test terdampak lulus dengan filter terarah.
- `FITUR_APLIKASI.md`, `tutorial.json`, dan `AGENTS.md` sudah diperbarui.
- Tidak ada `git commit` sampai diminta.
