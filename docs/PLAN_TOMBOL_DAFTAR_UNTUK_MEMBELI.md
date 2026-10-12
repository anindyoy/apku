# Plan: Mengaktifkan Tombol "Daftar untuk Membeli" di Landing Page

Dokumen ini adalah rencana kerja untuk menghubungkan tombol pada bagian **Harga Premium** di halaman publik `/` dengan alur order langganan yang sudah ada (modal **Konfirmasi order** di halaman Langganan).

> Catatan: rencana ini disusun dari `FITUR_APLIKASI.md`, `AGENTS.md`, dan `welcome.blade.php`. Nama class, method, dan file di bagian "Perubahan teknis" adalah **titik yang harus diverifikasi** terhadap source code sebelum dikerjakan. Source code dan test yang berlaku tetap menjadi sumber kebenaran.

## 1. Latar belakang

Kondisi saat ini:

- Setiap paket di landing page memiliki tombol **Daftar untuk membeli** yang hanya mengarah ke `filament.admin.auth.register`, tanpa membawa paket yang dipilih.
- Order hanya dapat dibuat di halaman Langganan lewat tombol **Beli paket**, lalu modal **Konfirmasi order** (metode pembayaran, voucher, ringkasan, total). Total final dihitung ulang di server.
- Registrasi melewati verifikasi email dan onboarding sebelum pengguna dapat memakai aplikasi.
- Landing page memuat paket aktif lewat `HomeController`; data tiap paket (`label`, `harga`, `per_bulan`, `hemat_bulan`, `persen`, `is_terbaik`) memakai perhitungan yang sama dengan halaman Langganan.

## 2. Keputusan perilaku (sudah disepakati)

| Situasi | Perilaku |
|---|---|
| Pengunjung belum punya akun mengklik tombol | Diarahkan ke registrasi dengan paket terbawa. Setelah registrasi, verifikasi email, dan onboarding selesai, pengguna langsung tiba di halaman Langganan dengan modal **Konfirmasi order** terbuka untuk paket itu. |
| Pengguna sudah login membuka landing page | Label tombol menjadi **Beli paket** dan langsung menuju halaman Langganan dengan modal order terbuka untuk paket yang dipilih. |
| Paket pilihan selama alur registrasi | Diingat sepanjang alur verifikasi email dan onboarding, lalu modal order dibuka di akhir. |

## 3. Keputusan teknis yang direkomendasikan

1. **Satu pintu masuk**: route publik baru (usulan `GET /beli/{paket}`, nama `beli-paket`) yang memvalidasi paket lalu mengarahkan sesuai status login. Landing page hanya perlu menautkan ke route ini, sehingga logika percabangan tidak tersebar di Blade.
2. **Paket pilihan disimpan di dua tahap**:
   - Sebelum akun ada: disimpan di **session** (`paket_dipilih_id`).
   - Setelah registrasi: dipindahkan ke kolom baru pada `users` (usulan `paket_langganan_dipilih_id`, nullable, FK ke `paket_langganans` dengan `nullOnDelete`).
   - Alasan memakai kolom database: tautan verifikasi email sering dibuka di perangkat atau browser lain, sehingga session asli hilang. Kolom ini membuat paket tetap "terbawa" sampai onboarding selesai.
3. **Kolom dibersihkan** saat modal order berhasil dibuka (atau saat order dibuat), agar modal tidak muncul berulang pada kunjungan berikutnya.
4. **Harga tidak pernah dipercaya dari URL atau session.** Parameter hanya memilih ID paket aktif; harga, voucher, dan total tetap dihitung ulang di server saat order dibuat.

## 4. Alur lengkap

```text
Landing page → klik tombol paket
        │
        ▼
GET /beli/{paket}  (validasi: paket ada & aktif)
        │
        ├── paket tidak valid → kembali ke /#harga + pesan "paket tidak tersedia"
        │
        ├── pengguna admin → tidak ada aksi beli (lihat butir 8)
        │
        ├── sudah login (non-admin) ──────────────► Langganan?paket={id} → modal order terbuka
        │
        └── belum login → simpan session → Registrasi
                                              │
                                              ▼
                              akun dibuat → pindahkan pilihan ke kolom users
                                              │
                                              ▼
                              verifikasi email (bisa di perangkat lain)
                                              │
                                              ▼
                              onboarding selesai
                                              │
                                              ▼
                              ada paket tersimpan & masih aktif?
                                 ├── ya → Langganan → modal order terbuka → kolom dibersihkan
                                 └── tidak → tujuan default setelah onboarding
```

## 5. Perubahan teknis

### 5.1 Data paket untuk landing page

- Pastikan setiap item daftar harga yang dikirim ke Blade menyertakan **`id`** paket. Perubahan dilakukan pada sumber perhitungan bersama (yang juga dipakai halaman Langganan) agar kedua halaman tetap konsisten.

### 5.2 Route dan controller pintu masuk

- Tambah route `beli-paket` (publik, dengan `throttle`) dan controller/aksi baru.
- Aksi memuat paket dari `paket_langganans` dengan syarat `is_active = true`.
- Percabangan: paket tidak valid, admin, sudah login, belum login (lihat diagram).
- Tujuan redirect selalu route internal yang tetap (tidak menerima URL dari input) sehingga tidak ada risiko open redirect.

### 5.3 Blade landing page (`welcome.blade.php`)

- Tombol pada bar ringkasan tiap paket berubah dari `route('filament.admin.auth.register')` menjadi `route('beli-paket', $paket['id'])`.
- Label memakai `@auth`: **Daftar untuk membeli** untuk tamu, **Beli paket** untuk pengguna login. Catatan teks bantu di bawah daftar harga disesuaikan agar tidak lagi menyebut "Daftar" pada pengguna login.
- Karena label bergantung status login, pastikan route `/` tidak dilayani dari cache halaman penuh (jika ada). Bila ada, gunakan pendekatan yang tidak membekukan label per pengguna.
- Styling tetap hanya Tailwind/Flowbite, tanpa CSS custom baru.

### 5.4 Registrasi

- Setelah akun berhasil dibuat (hook pada halaman registrasi Filament), baca `paket_dipilih_id` dari session, validasi paket masih aktif, simpan ke kolom `users.paket_langganan_dipilih_id`, lalu hapus dari session.
- Jika session kosong atau paket sudah nonaktif, kolom dibiarkan kosong tanpa error.

### 5.5 Migrasi

- Migrasi baru: tambah kolom nullable `paket_langganan_dipilih_id` pada `users` dengan FK `paket_langganans` (`nullOnDelete`, `cascadeOnUpdate`).
- Wajib memperbarui bagian **Skema database** di `FITUR_APLIKASI.md` dalam pekerjaan yang sama (aturan `AGENTS.md`).

### 5.6 Setelah onboarding

- Pada titik redirect akhir onboarding: jika `paket_langganan_dipilih_id` terisi dan paket masih aktif, arahkan ke halaman Langganan dengan paket terpilih; jika tidak, pakai tujuan default.
- Verifikasi bahwa alur verifikasi email dan onboarding tidak membuang pilihan ini (misalnya redirect "intended" bawaan Laravel yang menimpa tujuan).

### 5.7 Halaman Langganan

- Terima parameter `paket` (ID) dari route pintu masuk maupun dari kolom `users`.
- Saat halaman dimuat: validasi paket aktif, lalu buka modal **Konfirmasi order** dengan paket itu sudah terpilih (memakai mekanisme mount aksi Livewire/Filament yang sama dengan tombol **Beli paket**).
- Setelah modal terbuka, kosongkan `paket_langganan_dipilih_id`.
- Parameter tidak valid, paket nonaktif, atau pengguna admin: abaikan dan tampilkan halaman Langganan biasa, dengan notifikasi singkat bila paket sudah tidak tersedia.
- Tidak ada perubahan pada perhitungan harga, voucher, atau pembuatan order.

## 6. Kasus tepi

| Kasus | Penanganan |
|---|---|
| Paket dinonaktifkan admin di tengah alur | Pilihan diabaikan, pengguna tiba di Langganan biasa dengan notifikasi. |
| Pengguna menutup tab lalu kembali beberapa hari kemudian | Pilihan tetap ada di kolom `users`; modal terbuka sekali saat pertama masuk ke Langganan. |
| Pengguna mengklik beberapa paket berbeda sebelum mendaftar | Pilihan terakhir yang dipakai. |
| Verifikasi email dibuka di perangkat lain | Aman karena pilihan sudah dipindahkan ke database saat registrasi. |
| Akun lama mendaftar ulang atau sudah punya akun | Klik tombol saat belum login tetap ke registrasi; tautan Masuk pada halaman registrasi tetap membawa pilihan lewat session. |
| Admin mengklik tombol | Tidak membuat order. Perilaku detail ditentukan di butir 8. |
| Pengguna yang sedang trial Premium atau Premium aktif | Boleh membeli (perpanjangan); sisa masa aktif tetap ditambahkan seperti aturan yang ada. |
| Pengguna baru yang memenuhi syarat Coba Premium | Kartu CTA trial di Dashboard dan Langganan tidak terganggu; pembelian tetap pilihan terpisah. |

## 7. Pengujian

Semua test dijalankan lokal dengan `--filter` dan `--parallel`, contoh `php artisan test --filter="BeliPaketTest" --parallel`. Jangan memakai `assertSee`; pakai assertion terhadap status, redirect, session, kolom database, dan properti/aksi Livewire.

Daftar test yang perlu dibuat atau diubah:

1. Tamu mengakses `beli-paket` dengan paket aktif: redirect ke registrasi dan session berisi ID paket.
2. Paket tidak ada atau nonaktif: redirect ke `/#harga` dengan pesan; session tidak terisi.
3. Pengguna login non-admin: redirect ke Langganan dengan parameter paket.
4. Admin: tidak ada order atau modal; perilaku sesuai keputusan butir 8.
5. Registrasi dengan session berisi paket: kolom `users.paket_langganan_dipilih_id` terisi dan session dibersihkan.
6. Registrasi tanpa session atau dengan paket nonaktif: kolom tetap kosong, registrasi berhasil.
7. Selesai onboarding dengan paket tersimpan: redirect ke Langganan dengan paket terpilih.
8. Halaman Langganan dengan paket valid: modal **Konfirmasi order** terbuka untuk paket tersebut; kolom dibersihkan setelahnya.
9. Halaman Langganan dengan paket tidak valid: tidak ada modal, tampil halaman biasa.
10. Order yang dibuat dari modal ini menghasilkan total yang dihitung server (voucher dan snapshot tidak berubah dari perilaku sekarang).
11. Test landing page: tombol tamu dan tombol pengguna login mengarah ke route yang benar dengan label yang sesuai.
12. Migrasi: kolom ada, FK `nullOnDelete` bekerja saat paket dihapus.

## 8. Keputusan yang masih terbuka

1. **Perilaku untuk admin.** Usulan: tombol disembunyikan atau diganti tautan ke dashboard admin, karena navigasi admin memang tidak memuat menu pembelian.
2. **Cakupan pembersihan kolom.** Usulan: dibersihkan saat modal pertama kali terbuka. Alternatif: dibersihkan hanya saat order dibuat atau modal dibatalkan.
3. **Kedaluwarsa pilihan.** Usulan: tanpa batas waktu selama paket masih aktif. Alternatif: abaikan pilihan yang lebih tua dari 7 hari.

## 9. Pembaruan dokumentasi (wajib, bagian dari pekerjaan yang sama)

- `FITUR_APLIKASI.md`:
  - Bagian landing page publik (`/`): tombol paket kini membawa paket terpilih; label berbeda untuk tamu dan pengguna login.
  - Bagian Langganan premium: modal order dapat terbuka otomatis untuk paket terpilih.
  - Bagian Akun/Onboarding: pilihan paket dibawa selama alur registrasi.
  - Bagian **Skema database**: kolom baru pada `users`; perbarui tanggal ringkasan.
- `resources/content/tutorial.json`: perbarui topik langganan (cara membeli paket dari landing page, dua cara masuk: tamu dan pengguna login). Gunakan istilah **Sumber Dana**, bukan Dompet, bila disebut. Bila jumlah atau struktur topik berubah, sesuaikan test tutorial serta keterangan jumlah topik di `FITUR_APLIKASI.md`.

## 10. Urutan pengerjaan

1. Migrasi kolom `users.paket_langganan_dipilih_id` dan pembaruan skema di `FITUR_APLIKASI.md`.
2. Tambah `id` pada data paket landing page; buat route dan controller `beli-paket`.
3. Update Blade landing page (tautan dan label).
4. Hook registrasi untuk memindahkan session ke kolom.
5. Redirect akhir onboarding.
6. Pembukaan otomatis modal di halaman Langganan dan pembersihan kolom.
7. Tulis dan jalankan test (dengan `--filter` dan `--parallel`).
8. Perbarui `FITUR_APLIKASI.md` dan `tutorial.json`.
9. Uji manual end-to-end di `apku.test`: tamu baru, pengguna login, verifikasi email di browser lain, paket dinonaktifkan di tengah alur.

## 11. Risiko

| Risiko | Mitigasi |
|---|---|
| Redirect "intended" Laravel atau Filament menimpa tujuan setelah onboarding | Periksa urutan redirect; tambahkan test alur penuh (butir 7). |
| Halaman `/` ter-cache sehingga label tombol salah | Periksa middleware atau cache pada route `/`. |
| Pilihan paket menumpuk dan membuka modal tak terduga | Bersihkan kolom setelah modal terbuka; abaikan paket nonaktif. |
| Penyalahgunaan route publik | `throttle` pada route; ID divalidasi; tidak ada harga dari input. |

## 12. Kriteria selesai

- Tombol di landing page mengarah ke alur yang benar untuk tamu dan pengguna login.
- Paket pilihan terbawa melewati registrasi, verifikasi email (termasuk beda perangkat), dan onboarding.
- Modal **Konfirmasi order** terbuka otomatis dengan paket yang dipilih, dan total tetap dihitung server.
- Seluruh test baru lulus dengan filter terarah; test terdampak lain tidak rusak.
- `FITUR_APLIKASI.md` (termasuk skema database) dan `tutorial.json` sudah diperbarui.
- Tidak ada `git commit` sampai diminta.
