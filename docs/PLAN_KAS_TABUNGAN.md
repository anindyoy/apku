# Plan Fitur Kas Tabungan

Status: rencana, belum diimplementasikan.

## 1. Tujuan

Pengguna Premium dapat menandai sebuah kas sebagai **Kas Tabungan** yang memiliki target nominal (goal) dan tenggat (deadline) opsional. Aplikasi menampilkan persentase pencapaian dan peringatan tenggat, mengurutkan kas tabungan setelah kas biasa, serta menyediakan pengaturan apakah saldo kas tabungan ikut dihitung di total saldo.

## 2. Keputusan yang sudah dikonfirmasi

| Topik | Keputusan |
|---|---|
| Hitung progres | `saldo kas saat ini ÷ goal`, tanpa konsep setoran/penarikan khusus |
| Akses | Hanya Premium |
| Total saldo | Ada setting khusus untuk menentukan saldo kas tabungan ikut dihitung atau tidak |
| Persentase | Tampil `>100%` jika saldo melebihi goal; progres disembunyikan jika goal kosong |
| Deadline mendekat | "Kurang dari sebulan" = 1 bulan kalender |
| Deadline lewat | Ada informasi khusus (**Terlewat**) |
| Tempat tampil | Daftar Kas dan Dashboard |
| Urutan | Kas biasa dulu, lalu kas tabungan; di tiap kelompok urut nama; berlaku juga di semua dropdown pilih kas |
| Ubah tipe | Boleh, walau sudah punya transaksi; goal dan deadline dibiarkan di database tetapi tidak tampil saat bukan tabungan |
| Label radio | **Kas Biasa** (`is_tabungan = false`) dan **Kas Tabungan** (`true`) |

## 3. Temuan dari FITUR_APLIKASI.md

- Tabel `buku_kas` **sudah memiliki** kolom `goal` (integer nullable) dan `tanggal_goal` (date nullable). Plan ini memakai kolom tersebut sebagai goal dan deadline, bukan membuat kolom baru. Langkah pertama implementasi: telusuri pemakaian `goal` dan `tanggal_goal` di `app`, `resources`, `database/seeders`, dan test agar tidak bentrok dengan logika lama.
- Sudah ada fitur **Tabungan Emas** (tabel `tabungan_emas`, submenu, saklar admin). Itu fitur berbeda. Pada teks pengguna, gunakan istilah **Kas Tabungan** agar tidak tertukar.
- Daftar kas dan data kartu di-cache (daftar kas hingga tiga hari, kartu Dashboard 30 menit). Status deadline bergantung pada tanggal, jadi tidak boleh ikut membeku di cache (lihat bagian 6).

## 4. Skema database

Migrasi baru (tambahkan di `database/migrations`):

1. `buku_kas`
   - `is_tabungan` boolean default `false`.
   - Indeks `[user_id, is_tabungan, nama_buku]` untuk mendukung pengurutan.
   - `goal` dan `tanggal_goal` tidak diubah.
2. `users`
   - `hitung_tabungan_di_total` boolean default `true` (perilaku saat ini tidak berubah untuk akun yang ada).

Pembaruan wajib di bagian **Skema database** pada `FITUR_APLIKASI.md` dalam pekerjaan yang sama.

## 5. Aturan bisnis

### 5.1 Hak akses
- Hanya pemilik dengan Premium aktif yang dapat menjadikan kas sebagai tabungan, mengubah `goal`/`tanggal_goal`, atau mengembalikannya ke kas biasa. Validasi di server (Policy/Form Request), bukan hanya menyembunyikan field.
- Premium kedaluwarsa: data `is_tabungan`, `goal`, dan `tanggal_goal` dipertahankan, tetapi kas diperlakukan seperti kas biasa (tanpa progres dan peringatan, tanpa pengurutan khusus) dan tidak dapat diubah tipenya. Mengikuti perilaku Premium kedaluwarsa yang sudah ada.
- Kas bersama: Editor dan Viewer melihat progres secara read-only; hanya pemilik yang mengubah tipe. (Asumsi, lihat bagian 10.)

### 5.2 Validasi form
- `is_tabungan`: boolean.
- `goal`: nullable, integer, minimal 1, mengikuti format rupiah titik ribuan yang sudah ada.
- `tanggal_goal`: nullable, tanggal; saat dibuat atau diubah nilainya harus hari ini atau setelahnya.
- Saat `is_tabungan` false, field goal dan deadline disembunyikan tetapi nilainya tetap dikirim (`dehydratedWhenHidden()`), sehingga tidak terhapus.

### 5.3 Perhitungan progres
Dihitung hanya jika kas tabungan, Premium aktif, dan `goal` tidak null.
- `persen = floor(max(saldo, 0) / goal * 100)`. Dibulatkan ke bawah agar 99,6% tidak tampil 100% sebelum benar-benar tercapai.
- Teks menampilkan nilai penuh (misal `125%`); bar visual dibatasi 100% lebar.

### 5.4 Status tabungan
Satu status per kas, urutan prioritas dari atas:

| Status | Syarat | Tampilan |
|---|---|---|
| Tercapai | goal ada dan `saldo >= goal` | Badge hijau **Tercapai**; peringatan deadline tidak tampil, termasuk jika deadline sudah lewat |
| Terlewat | deadline ada, `deadline < hari ini`, belum tercapai | Badge merah **Terlewat** + tanggal deadline |
| Mendekati deadline | deadline ada, `hari ini <= deadline < hari ini + 1 bulan kalender` (`addMonthNoOverflow`), belum tercapai | Informasi kuning, misal "5 hari lagi" atau "Berakhir hari ini" |
| Berjalan | selain di atas | Hanya progres (jika goal ada) |

Catatan: saran untuk "goal sudah tercapai" adalah menyembunyikan peringatan deadline karena target sudah terpenuhi, sehingga pengguna tidak mendapat peringatan yang tidak relevan. Deadline tanpa goal tetap menghasilkan status Terlewat atau Mendekati (tanpa progres).

## 6. Implementasi

1. **Model `BukuKas`**
   - Tambah `is_tabungan` pada `$casts` (boolean).
   - Scope `urutanTabungan()`: `orderBy('is_tabungan')->orderBy('nama_buku')`.
   - Terapkan scope ini di daftar Kas dan semua dropdown pilih kas (form transaksi, form transfer, import, pencocokan saldo, filter transaksi dan laporan). Telusuri semua `Select` kas agar konsisten.
2. **Kelas pembantu** (misal `app/Support/ProgresTabungan.php`): menerima kas, tanggal hari ini, dan status Premium; mengembalikan `persen`, `status`, `sisaHari`. Seluruh logika di satu tempat agar mudah diuji.
3. **Form Kas (Filament)**
   - `Radio::make('is_tabungan')->boolean(...)` dengan label **Kas Biasa** / **Kas Tabungan**, default Kas Biasa. Helper text singkat, misal "Kas Tabungan punya target dan tenggat".
   - Field goal dan deadline tampil hanya jika Kas Tabungan dipilih (`live()`).
   - Akun Reguler: radio dinonaktifkan dengan keterangan fitur Premium.
4. **Daftar Kas**: kartu menampilkan badge tipe, bar progres + persentase (jika goal ada), dan informasi status. Pakai Tailwind dan komponen Flowbite, tanpa CSS custom.
5. **Dashboard** (tab Kas & Sumber Dana): kas tampil dengan urutan yang sama; kas tabungan menampilkan progres dan status ringkas.
6. **Setting total saldo**: toggle **Hitung saldo kas tabungan di total saldo** pada tombol **Pengaturan tampilan** halaman Kas, menyimpan ke `users.hitung_tabungan_di_total`. Memengaruhi total saldo di Dashboard (dan total lain yang menjumlahkan saldo kas, hasil telusuran). Perubahan membatalkan cache terkait.
7. **Cache**: simpan data mentah (saldo, goal, deadline) di cache, hitung `status` dan `sisaHari` saat render, atau sertakan tanggal hari ini pada kunci cache. Tambahkan invalidasi saat `is_tabungan`, `goal`, atau `tanggal_goal` berubah, dan saat setting total saldo berubah.
8. **Seeder demo**: tambahkan beberapa kas tabungan (belum tercapai, mendekati deadline, terlewat, tercapai). Seeder dikecualikan dari testing.
9. **Istilah**: gunakan "Sumber Dana" bukan "dompet" dan "Cocokkan Saldo" bukan "audit" pada teks yang terlihat pengguna; komentar kode dalam Bahasa Indonesia.

## 7. Pengujian

Semua perintah lokal memakai `--filter` dan `--parallel`; tanpa `assertSee`; tanpa full suite.

```bash
php artisan test --filter="ProgresTabunganTest" --parallel
php artisan test --filter="KasTabunganFormTest" --parallel
php artisan test --filter="KasTabunganUrutanTest" --parallel
php artisan test --filter="TotalSaldoTabunganTest" --parallel
php artisan test --filter="TutorialTest" --parallel
```

Cakupan test:
- **ProgresTabunganTest**: persen (0, tengah, tepat 100, di atas 100, saldo negatif), goal null menyembunyikan progres, batas 1 bulan kalender (termasuk akhir bulan, misal 31 Jan), deadline hari ini, Terlewat, Tercapai menimpa peringatan, deadline tanpa goal, Premium kedaluwarsa.
- **KasTabunganFormTest**: Reguler ditolak di server, Premium dapat membuat dan mengubah, validasi goal dan tanggal, goal dan deadline tetap tersimpan saat tipe diubah ke biasa dan tidak tampil, Editor/Viewer tidak dapat mengubah tipe.
- **KasTabunganUrutanTest**: kas biasa sebelum tabungan, urut nama dalam kelompok, berlaku di daftar dan dropdown.
- **TotalSaldoTabunganTest**: toggle mengubah total saldo Dashboard, default true, cache ikut diperbarui.
- **TutorialTest** (yang terdampak): struktur JSON tutorial tetap valid.

## 8. Dokumentasi dan tutorial (wajib, dalam pekerjaan yang sama)

- `FITUR_APLIKASI.md`: bagian Kas (backend dan frontend), Dashboard, setting total saldo, dan Skema database (`buku_kas.is_tabungan`, `users.hitung_tabungan_di_total`, catatan pemakaian `goal`/`tanggal_goal`).
- `resources/content/tutorial.json`: perbarui topik Kas dan Dashboard (cara membuat Kas Tabungan, goal, deadline, arti status, setting total saldo, batasan Premium). Sesuaikan jumlah topik dan keterangan terkait jika bertambah, lalu jalankan test tutorial yang terdampak.

## 9. Urutan pengerjaan

1. Telusuri pemakaian `goal`/`tanggal_goal` dan semua `Select` kas.
2. Migrasi dan model (`is_tabungan`, setting user).
3. Kelas `ProgresTabungan` + test-nya.
4. Scope urutan dan penerapan di semua dropdown.
5. Form Kas, lalu daftar Kas, lalu Dashboard.
6. Setting total saldo + invalidasi cache.
7. Seeder demo.
8. `FITUR_APLIKASI.md` dan `tutorial.json`.
9. Jalankan test terfilter. Tidak ada `git commit` kecuali diminta.

## 10. Asumsi yang perlu dikonfirmasi

1. Setting total saldo bersifat **per pengguna**, default **ikut dihitung**, dan ditempatkan di Pengaturan tampilan halaman Kas.
2. Kas bersama: hanya pemilik yang dapat mengubah tipe; kolaborator melihat progres read-only.
3. Premium kedaluwarsa: kas tampil seperti kas biasa tetapi data tabungan tidak dihapus.
4. Persentase dibulatkan ke bawah.
5. Deadline baru wajib hari ini atau setelahnya; deadline lama yang sudah lewat tetap boleh dan menghasilkan status Terlewat.
6. Urutan khusus ini berlaku sebagai urutan default; jika nanti ada pengurutan manual oleh pengguna, prioritas kas biasa sebelum tabungan perlu diputuskan terpisah.
