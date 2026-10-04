# Rencana Perubahan: Aktivitas → Kategori (APKu)

Dokumen ini merangkum rekomendasi dan rencana kerja untuk mengubah entitas **Aktivitas transaksi** (tabel `jenis_transaksi`) menjadi **Kategori** yang opsional pada transaksi dan terikat pada kas.

- Tanggal: 3 Oktober 2026
- Status aplikasi: belum production, belum ada data nyata (133 class, 104 route)
- Acuan: `FITUR_APLIKASI.md` dan laporan coverage (line 86,85%)
- Branch kerja: `refactor/kategori` (sudah di-push ke `origin`)

## Progres

Pembaruan terakhir: 4 Oktober 2026.

| Fase | Status | Commit | Catatan |
|---|---|---|---|
| 0. Baseline | Selesai sebagian | - | Branch dibuat dan daftar file terpetakan (71 file di luar laporan coverage). Full suite tidak dijalankan di lokal sesuai aturan proyek; baseline memakai angka coverage 86,85% yang sudah ada. |
| 1. Test pengaman | Selesai | `d9edc4c` | 12 test di `tests/Feature/KategoriPengamanTest.php`, lulus di lokal. |
| 2. Rename murni | Selesai | `13c8a09` | 302 test terdampak lulus di lokal. |
| 3. Kategori nullable | Selesai | `8e9bb31` | 156 test terdampak lulus di lokal. |
| 4. Ikat ke kas | Selesai | `5b3ce62` | Run lokal: 244 lulus, 6 gagal. Keenam gagalnya ternyata bug test (variabel tak terdefinisi, ekspektasi usang), diperbaiki di `f803886`. |
| 5. Tipe dan penutup | Selesai | - | Kolom `tipe` menerima `Semua`, setting pisah/gabung ditambahkan. Lihat §3.3 dan §6a untuk detail keputusan. |

Commit tambahan `0ce48e1`: aturan test lokal di `AGENTS.md` dan pemicu CI untuk branch ini.

### Yang perlu ditindaklanjuti

- Hapus `refactor/kategori` dari pemicu `push` di `.github/workflows/tests.yml` sebelum merge ke `main`.
- Perbandingan coverage dengan baseline (Fase 0) sebaiknya dikerjakan di CI sebelum merge ke `main`.

---

## 1. Tujuan

Mempercepat pencatatan transaksi dengan membuat klasifikasi menjadi opsional, sekaligus menjadikannya bagian dari buku kas agar cocok untuk kas bersama.

## 2. Keputusan yang sudah ditetapkan

| # | Topik | Keputusan |
|---|---|---|
| 1 | Alasan perubahan | Soal istilah ("Aktivitas" menjadi "Kategori") |
| 2 | Cakupan kas | Satu kategori dapat dipakai di beberapa kas |
| 3 | Pemisahan tipe | Pemasukan dan pengeluaran dipisah atau digabung, sesuai setting user |
| 4 | Hak Editor | Editor boleh mengelola kategori |
| 5 | Transfer kas dan dompet | Kategori tetap kosong |
| 6 | Alasan nullable | Mempercepat input |
| 7 | Laporan | Transaksi tanpa kategori tampil sebagai baris "Tanpa kategori" |
| 8 | Pemindahan kas | User menentukan kategori di kas tujuan, atau mengosongkannya |
| 9 | Import | Opsi membuat kategori yang belum ada tetap tersedia, dibuat di kas pilihan user |
| 10 | Data | Belum ada data production |

## 3. Desain yang direkomendasikan

### 3.1 Penamaan: rename penuh sekarang

Karena belum ada data nyata, ubah nama tabel, model, kolom FK, dan resource Filament sekarang. Biayanya paling murah saat ini dan akan naik setelah production. Lakukan sebagai commit terpisah tanpa perubahan perilaku (lihat Fase 2).

### 3.2 Skema

Sketsa berikut perlu disesuaikan dengan nama tabel dan kolom yang sebenarnya.

```php
Schema::rename('jenis_transaksi', 'kategori');

Schema::create('kategori_kas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
    $table->foreignId('kas_id')->constrained('kas')->cascadeOnDelete();
    $table->unique(['kategori_id', 'kas_id']);
});

// Pada tabel transaksi:
// - ganti nama kolom FK menjadi kategori_id
// - jadikan nullable
```

Opsional, untuk integritas di level database: foreign key komposit dari `transaksi (kas_id, kategori_id)` ke `kategori_kas (kas_id, kategori_id)`. Karena `kategori_id` nullable, baris tanpa kategori lolos pengecekan secara otomatis. Periksa dukungan engine database yang dipakai.

### 3.3 Aturan bisnis

- Transaksi di kas X hanya boleh memakai kategori yang terhubung ke kas X (validasi di form request/service, plus FK komposit bila dipakai).
- `kategori_id` selalu null pada transfer kas dan transfer dompet.
- Kolom `tipe` pada kategori berisi `pemasukan`, `pengeluaran`, atau `semua`.
- Setting user hanya mengatur tampilan: dropdown difilter sesuai tipe transaksi atau tidak, dan tipe default saat membuat kategori baru. Data tidak diubah ketika setting dialihkan.
- Audit saldo: jangan bergantung pada nama kategori sistem "Audit Saldo". Beri penanda lain pada transaksi (kolom sumber atau relasi ke audit). Periksa dulu bagaimana "Transaksi audit saldo" dikenali di kode saat ini.
- Tipe `Semua` (Fase 5): kategori bertipe `Semua` selalu ikut tampil pada dropdown kategori transaksi, baik saat setting pengguna "pisah" (dropdown difilter sesuai Pemasukan/Pengeluaran) maupun "gabung" (dropdown tidak difilter). Tanpa aturan ini, kategori `Semua` tidak akan terlihat sama sekali dalam mode pisah.
- Batasan yang disengaja (Fase 5): import transaksi (`ImportTransaksiService::cariKategori()`) tidak diubah untuk mengenali atau membuat kategori bertipe `Semua`; pencocokan otomatis tetap berdasarkan jenis baris (`Pemasukan`/`Pengeluaran`). Ini bukan kelalaian — alur import sudah selesai di Fase 4 dan tidak termasuk cakupan Fase 5.

### 3.4 Keputusan yang masih terbuka

Status: ketiga saran di bawah sudah diterapkan di Fase 4.

| Topik | Saran |
|---|---|
| Kepemilikan kategori (Editor bisa membuat, kategori bisa lintas kas) | `user_id` = pemilik kas tempat kategori dibuat, ditambah `dibuat_oleh` untuk Editor pembuat. Kategori hanya boleh dihubungkan ke kas milik pemilik yang sama. |
| Melepas kategori dari kas yang transaksinya masih memakainya | Blokir, dengan tombol "kosongkan lalu lepas" setelah konfirmasi. |
| Keunikan nama | Unik per `(user_id, nama, tipe)`. |

## 4. Area yang terdampak

| Area | Perubahan |
|---|---|
| Import | Pemetaan kolom kategori, opsi "Buat kategori yang belum tersedia" (di kas pilihan user), nilai kosong menjadi null, template XLSX, laporan error |
| Laporan (PDF, Excel, tab) | Baris "Tanpa kategori" pada ringkasan, persentase, dan rincian |
| Hapus kas dengan pemindahan transaksi | Langkah pemetaan kategori ke kas tujuan, atau kosongkan |
| Onboarding | Kategori awal dibuat dan dihubungkan ke kas utama |
| Policy dan hak akses | Editor boleh mengelola kategori |
| Kas bersama dan halaman publik | Tampilan dan pencarian kolom kategori |
| Pencarian global dan dashboard | Istilah dan kolom kategori |
| Cache opsi input (3 hari) | Invalidasi saat kategori atau pivot berubah |
| Filament | Resource Aktivitas menjadi Kategori, lokasi menu, form transaksi |
| Konten dan data | `resources/content/tutorial.json`, `FITUR_APLIKASI.md`, seeder demo |

## 5. Pengaman: kondisi test saat ini

Line coverage 86,85% (4585 / 5279) memberi pengaman yang baik, tetapi tidak merata:

| Area | Lines | Fungsi | Catatan |
|---|---|---|---|
| Filament | 91,6% | 80,2% | Kuat |
| Models | 89,0% | 77,4% | Cukup kuat |
| Observers | 95,2% | 90,0% | Kuat |
| **Services** | **77,7%** | **43,8%** | Kemungkinan besar logika transaksi, import, dan laporan. **Prioritas utama.** |
| **Policies** | **71,7%** | 71,1% | Hanya 1 dari 9 class tercakup penuh. Berisiko untuk perubahan hak Editor. |
| **Jobs** | **65,5%** | 33,3% | Import antrean (1.001 sampai 10.000 baris) ikut terdampak. |
| Notifications | 47,8% | 78,9% | Dampak relatif kecil |

Catatan: coverage hanya mengukur baris yang dieksekusi, bukan kualitas assertion. Rincian per file di laporan coverage (halaman Services, Policies, Jobs) perlu dilihat untuk menentukan test mana yang paling mendesak.

## 6. Rencana kerja bertahap

Pecah menjadi commit atau PR terpisah supaya perubahan struktur tidak bercampur dengan perubahan perilaku.

### Fase 0: Baseline

```bash
git switch -c refactor/kategori
php artisan test
grep -rniE "jenis_?transaksi|aktivitas" app database resources routes tests -l | sort
grep -rniE "jenis_?transaksi|aktivitas" app database resources routes tests | wc -l
```

Hasil: jumlah test lulus, angka coverage awal, dan daftar file yang menyebut aktivitas.

### Fase 1: Test pengaman

Tambahkan test untuk bagian lemah sebelum merombak:

- Import sinkron dan antrean (termasuk pembatalan batch dan pembuatan aktivitas otomatis).
- Ringkasan laporan per aktivitas (persentase, filter kas dan dompet).
- Pemindahan transaksi saat menghapus kas.
- Policy Editor (membuat, mengubah, menghapus).

### Fase 2: Rename murni (tanpa perubahan perilaku)

- Migration rename tabel dan kolom FK, model, relasi, resource Filament, label UI, tutorial, dan dokumentasi.
- Seluruh test harus tetap hijau.

### Fase 3: Kategori nullable

- Ubah kolom FK menjadi nullable.
- Tambahkan opsi kosong pada form transaksi dan import.
- Tambahkan baris "Tanpa kategori" pada laporan dan ekspor.
- Pastikan transfer tetap tanpa kategori.

### Fase 4: Ikat ke kas

- Pivot `kategori_kas`, validasi transaksi dan kategori harus satu kas, dan penanganan melepas kategori.
- Alur pemindahan kas: pemetaan kategori atau kosongkan.
- Onboarding dan import (membuat kategori di kas pilihan user).
- Policy: Editor boleh mengelola kategori, sesuai keputusan kepemilikan di bagian 3.4.
- Invalidasi cache opsi input.

### Fase 5: Pemisahan tipe dan penutup

- Kolom `tipe`, setting user, dan filter dropdown.
- Rapikan seeder demo, `tutorial.json`, dan `FITUR_APLIKASI.md`.
- Jalankan ulang test lengkap dan bandingkan coverage dengan baseline Fase 0.

## 6a. Catatan pelaksanaan

Hal yang berbeda dari, atau menambah, rencana semula:

- **Penamaan (Fase 2)**: tabel `jenis_transaksi` menjadi `kategori`, kolom `nama_jenis` menjadi `nama`, FK `jenis_transaksi_id` menjadi `kategori_id`, model `JenisTransaksi` menjadi `Kategori`. Tabel kas bernama `buku_kas`, jadi pivot memakai kolom `buku_kas_id`. Istilah umum "aktivitas terakhir/terbaru" diganti "pembaruan terakhir/terbaru" dan "Transaksi terbaru".
- **Audit saldo (Fase 3)**: penanda `audit_saldo_dompet_detail_id` ternyata sudah ada. Kategori sistem "Audit Saldo" dan kolom `is_system` dihapus; transaksi audit kini tanpa kategori dan labelnya diturunkan dari relasi audit.
- **Nilai tipe**: tetap `Pemasukan` dan `Pengeluaran` (huruf kapital) agar selaras dengan kolom `jenis` transaksi. `Semua` ditambahkan di Fase 5.
- **Hak Editor (Fase 4)**: Editor hanya dapat mengubah atau menghapus kategori yang seluruh kasnya dapat ia sunting. Editor di kas bersama memakai kategori kas itu, bukan kategori pribadinya; migrasi mengosongkan kategori pada transaksi lama yang melanggar.
- **Import (Fase 4)**: kategori baru dibuat dan dihubungkan ke kas pada baris transaksinya, bukan ke satu kas pilihan di form. Kategori pemilik yang belum terhubung ke kas baris itu ikut dihubungkan bila opsi buat otomatis aktif.
- **Kas baru (Fase 4, tambahan)**: pilihan "Pakai semua kategori saya di kas ini", aktif secara default.
- **Halaman Kategori (Fase 4)**: dua panel pemasukan/pengeluaran diganti satu tabel dengan kolom nama, tipe, dan kas, serta aksi Tambah, Ubah, Lepas dari kas, dan Hapus.
- **FK komposit tidak dipasang**: bentrok dengan `nullOnDelete` pada `kategori_id` dan dengan alur pemindahan kas. Konsistensi kas dan kategori dijaga di `TransaksiService`, `KategoriService`, dan `BukuKasService`.
- **Cache opsi input**: opsi kategori kini di-cache per kas dan dibersihkan saat kategori atau pivot berubah.
- **Dokumentasi**: `tutorial.json`, `FITUR_APLIKASI.md`, dan `docs/PRD_APKu.md` sudah diperbarui sampai Fase 4, tidak menunggu Fase 5.

### Fase 5: ringkasan pelaksanaan

- Kolom `tipe` (enum database + `Kategori::TIPE`) menerima `Semua`, lewat migrasi `ALTER TABLE` (belum ada data production, jadi aman tanpa migrasi data kompleks).
- Setting user `pisahkan_tipe_kategori` (boolean, default `true`, kolom baru pada `users`) mengatur filter dropdown kategori (`Transaksi::opsiKategori()`) dan tipe default saat membuat kategori baru di halaman Filament Kategori. Validasi server (`KategoriService::pastikanAtributValid()`) tidak perlu diubah — begitu `Kategori::TIPE` memuat `Semua`, pengecekan `in_array` yang sudah ada otomatis menerimanya.
- Seeder demo dirapikan: nama kategori dihumanisasi, variabel diberi nama deskriptif, dan tipe `Semua` ditambahkan sebagai contoh. Keterkaitan kategori ke kas tidak perlu diperbaiki — sudah ditangani `TransaksiSeeder` lewat `INSERT INTO kategori_kas ... SELECT ...`.
- Istilah "aktivitas" sudah bersih sejak Fase 2; satu-satunya sisa adalah komentar docblock historis di `tests/Feature/KategoriPengamanTest.php` yang tidak berdampak ke pengguna.
- `tutorial.json` dan `FITUR_APLIKASI.md` diperbarui untuk tipe `Semua` dan setting baru.

## 7. Risiko dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Perubahan istilah terlewat di banyak tempat (133 class, 104 route) | Grep di Fase 0, rename murni di Fase 2, cek ulang di Fase 5 |
| Logika laporan dan import rusak diam-diam | Test pengaman Fase 1 sebelum mengubah apa pun |
| Kategori tidak konsisten dengan kas pada transaksi | Validasi server dan FK komposit |
| Hak Editor terlalu longgar atau terlalu ketat | Tambahkan test policy untuk Editor dan Viewer |
| Audit saldo kehilangan penanda karena kategori sistem hilang | Tentukan penanda pengganti sebelum Fase 3 (selesai: memakai `audit_saldo_dompet_detail_id`) |
| Cache opsi input basi | Invalidasi pada perubahan kategori dan pivot |

## 8. Definisi selesai

- [ ] Seluruh test lulus dan coverage tidak turun dari baseline. (menunggu CI)
- [x] Tidak ada lagi istilah "aktivitas" di UI, tutorial, dokumentasi, dan laporan. (dokumen rencana lama di `docs/` sengaja tidak diubah; dicek ulang di Fase 5, sisa hanya komentar docblock historis)
- [x] Transaksi tanpa kategori dapat dibuat, diimpor, dan tampil sebagai "Tanpa kategori" di laporan dan ekspor.
- [x] Transaksi tidak dapat memakai kategori yang tidak terhubung ke kas-nya.
- [x] Alur hapus kas dengan pemindahan transaksi menangani kategori sesuai pilihan user.
- [x] Pemisahan tipe sesuai setting user. (Fase 5: kolom `tipe` menerima `Semua`, setting `pisahkan_tipe_kategori` mengatur filter dropdown dan tipe default)
