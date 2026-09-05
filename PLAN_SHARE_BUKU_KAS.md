# Rencana Implementasi Fitur Share Buku Kas

## 1. Tujuan

Mengaktifkan fitur berbagi buku kas agar pemilik dapat memberikan akses kepada pengguna lain untuk melihat atau ikut mencatat transaksi pada buku kas tertentu tanpa membuka akses ke buku kas, dompet, kategori, dan data keuangan pribadi lainnya.

## 2. Kondisi Implementasi Saat Ini

Proyek sudah memiliki fondasi berikut:

- model `ShareBuku`;
- tabel `share_buku` dengan kolom `buku_kas_id`, `user_id`, dan `privilege`;
- pilihan hak akses `viewer` dan `editor`;
- resource Filament beserta halaman daftar, tambah, dan edit;
- test dasar untuk membuka halaman dan membuat share.

Namun fitur belum dapat dianggap aktif karena:

- menu **Share Buku** masih disembunyikan;
- form masih menerima ID buku kas dan pengguna secara mentah;
- daftar share belum dibatasi berdasarkan pemilik buku kas;
- belum ada constraint untuk mencegah share duplikat;
- belum ada aturan yang melarang pemilik membagikan buku kepada dirinya sendiri;
- `UserScope` pada buku kas dan transaksi hanya mengenali pemilik data;
- hak `viewer` dan `editor` belum diterapkan pada query, form, service, laporan, pencarian, dan aksi transaksi;
- transaksi juga terhubung ke dompet pribadi sehingga akses buku kas tidak boleh otomatis membuka akses dompet.

## 3. Keputusan Produk untuk MVP

### 3.1 Cara membagikan buku

- Hanya buku kas milik sendiri yang dapat dibagikan.
- Buku dibagikan kepada pengguna yang sudah terdaftar dan terverifikasi.
- Pengguna tujuan dipilih melalui alamat email, bukan ID.
- Admin tidak dapat menjadi penerima share.
- Pemilik tidak dapat membagikan buku kepada dirinya sendiri.
- Satu pengguna hanya boleh memiliki satu share aktif untuk satu buku kas.
- Pemilik menentukan tanggal mulai dan tanggal berakhir akses. Tanggal berakhir boleh dikosongkan untuk akses tanpa batas waktu.
- Pemilik dapat mengubah hak akses atau mencabut share kapan saja.
- Undangan melalui email kepada pengguna yang belum terdaftar belum termasuk MVP.

### 3.2 Hak akses

| Kemampuan | Pemilik | Editor | Viewer |
| --- | ---: | ---: | ---: |
| Melihat buku kas | Ya | Ya | Ya |
| Melihat seluruh transaksi buku | Ya | Ya | Ya |
| Melihat laporan buku | Ya | Ya | Ya |
| Membuat transaksi pada buku | Ya | Ya | Tidak |
| Mengubah transaksi sendiri | Ya | Ya | Tidak |
| Menghapus transaksi sendiri | Ya | Ya | Tidak |
| Mengubah transaksi pengguna lain | Tidak | Tidak | Tidak |
| Transfer saldo dari/ke buku bersama | Ya | Tidak | Tidak |
| Mengubah nama, target, atau status buku | Ya | Tidak | Tidak |
| Menghapus buku | Ya | Tidak | Tidak |
| Mengelola anggota | Ya | Tidak | Tidak |

Dalam tabel tersebut, transaksi sendiri berarti transaksi dengan `user_id` pengguna yang sedang aktif. Pemilik tidak boleh mengubah atau menghapus transaksi yang dibuat editor karena tindakan tersebut juga akan mengubah saldo dompet pribadi editor.

### 3.3 Aturan dompet dan kategori

- Share hanya memberikan akses ke buku kas, tidak ke dompet.
- Editor mencatat transaksi menggunakan dompet aktif miliknya sendiri.
- Editor menggunakan kategori miliknya sendiri yang sesuai dengan tipe transaksi.
- Nama dan saldo dompet milik pengguna lain tidak ditampilkan kepada anggota buku.
- Daftar transaksi bersama menampilkan pembuat transaksi, tetapi kolom dompet hanya terlihat pada transaksi milik pengguna aktif.
- Ringkasan saldo buku kas menghitung seluruh transaksi dalam buku.
- Ringkasan saldo dompet tetap hanya menghitung transaksi pada dompet milik pengguna aktif.
- Transfer buku kas dan pemindahan saldo dompet pada buku bersama dibatasi untuk pemilik pada MVP.

## 4. Alur Pengguna

### 4.1 Pemilik membagikan buku

1. Pemilik membuka halaman **Buku Kas**.
2. Pemilik memilih aksi **Bagikan** pada buku yang diinginkan.
3. Sistem menampilkan daftar anggota yang sudah memiliki akses.
4. Pemilik memilih **Tambah Anggota**.
5. Pemilik memasukkan atau memilih email pengguna terdaftar.
6. Pemilik memilih akses `Viewer` atau `Editor`.
7. Pemilik menentukan masa aktif share.
8. Sistem memvalidasi kepemilikan buku, penerima, masa aktif, dan share duplikat.
9. Sistem menyimpan share dan mengirim notifikasi dalam aplikasi kepada penerima.

### 4.2 Penerima mengakses buku bersama

1. Penerima memperoleh notifikasi bahwa sebuah buku dibagikan kepadanya.
2. Buku muncul pada halaman **Buku Kas** di bagian **Dibagikan kepada saya**.
3. Penerima membuka buku untuk melihat transaksi dan laporan.
4. Jika memiliki akses `Editor`, penerima dapat mencatat pemasukan atau pengeluaran menggunakan dompet dan kategorinya sendiri.
5. Jika memiliki akses `Viewer`, seluruh aksi perubahan disembunyikan dan tetap ditolak oleh otorisasi server.

### 4.3 Pemilik mengubah atau mencabut akses

1. Pemilik membuka daftar anggota buku.
2. Pemilik mengubah akses `Viewer`/`Editor` atau memilih **Cabut akses**.
3. Setelah dicabut, buku tidak lagi dapat diakses penerima.
4. Transaksi yang sebelumnya dibuat penerima tetap tersimpan sebagai riwayat buku dan tetap memengaruhi dompet pembuatnya.

### 4.4 Masa aktif berakhir

1. Share berstatus `Terjadwal` sebelum tanggal mulai.
2. Share berstatus `Aktif` ketika waktu saat ini berada dalam rentang akses.
3. Share berstatus `Kedaluwarsa` setelah tanggal berakhir terlewati.
4. Share terjadwal atau kedaluwarsa tidak memberikan akses baca maupun tulis.
5. Pemilik dapat memperpanjang tanggal berakhir untuk mengaktifkan kembali akses tanpa membuat share duplikat.

## 5. Rancangan Data

### 5.1 Penyempurnaan tabel `share_buku`

Tambahkan atau pastikan aturan berikut:

- unique index gabungan `buku_kas_id` dan `user_id`;
- index `user_id` dan `privilege` untuk query buku yang dibagikan;
- enum atau validation rule yang hanya menerima `viewer` dan `editor`;
- kolom `berlaku_mulai` bertipe datetime dan wajib, dengan nilai bawaan waktu saat share dibuat;
- kolom `berlaku_sampai` bertipe datetime dan nullable untuk akses tanpa batas waktu;
- index gabungan `user_id`, `berlaku_mulai`, dan `berlaku_sampai` untuk pencarian akses aktif;
- foreign key tetap menggunakan cascade delete ketika buku atau pengguna dihapus.

Kolom opsional untuk pengembangan berikutnya:

- `invited_by` untuk audit pemberi akses;
- `accepted_at` jika kelak menggunakan alur undangan;
- `revoked_at` jika pencabutan akses perlu disimpan sebagai histori, bukan dihapus permanen.

Untuk MVP, record share dapat dihapus ketika akses dicabut. Jika audit perubahan akses menjadi kebutuhan wajib, gunakan soft delete atau tabel aktivitas terpisah.

### 5.2 Relasi model

Tambahkan relasi yang eksplisit:

- `BukuKas::shares()`;
- `BukuKas::anggota()` melalui `share_buku`;
- `User::shareBukuDiterima()`;
- `User::bukuKasDibagikan()` melalui `share_buku`;
- relasi balik yang sudah ada pada `ShareBuku` dipertahankan dan diberi return type jika konsisten dengan model lain.

## 6. Lapisan Otorisasi

### 6.1 Policy

Buat `BukuKasPolicy`, `ShareBukuPolicy`, dan aturan transaksi yang menjadi sumber kebenaran untuk:

- `view`: pemilik atau anggota aktif;
- `update` dan `delete` buku: hanya pemilik;
- `share`: hanya pemilik;
- `viewAnyShare`: hanya menampilkan share dari buku milik pengguna;
- `createTransaction`: pemilik atau editor;
- `updateTransaction` dan `deleteTransaction`: pembuat transaksi yang juga masih memiliki akses editor, atau pemilik untuk transaksi miliknya sendiri;
- viewer tidak pernah memiliki izin mutasi.

Jangan menjadikan visibilitas tombol Filament sebagai batas keamanan. Semua operasi harus melakukan pemeriksaan policy/service di server.

### 6.2 Query akses buku

Jangan langsung memperluas `UserScope` menjadi kondisi global kompleks tanpa test menyeluruh. Buat scope atau service akses yang jelas, misalnya:

- `BukuKas::accessibleBy(User $user)`;
- `BukuKas::ownedBy(User $user)`;
- `Transaksi::accessibleBy(User $user)` berdasarkan kepemilikan buku atau share aktif;
- `User::hakAksesPada(BukuKas $bukuKas)` yang mengembalikan `owner`, `editor`, `viewer`, atau `null`.

Resolver hanya menganggap share aktif jika `berlaku_mulai` tidak melebihi waktu saat ini dan `berlaku_sampai` kosong atau belum terlewati. Status masa aktif harus dihitung dari waktu server, bukan dikirim dari form pengguna.

Query untuk form pengelolaan buku harus tetap memakai buku milik sendiri, sedangkan daftar dan laporan boleh memakai buku yang dapat diakses.

### 6.3 Perubahan pada `TransaksiService`

Pisahkan pemeriksaan akses buku dan dompet:

- buku kas boleh merupakan milik pengguna atau dibagikan dengan akses editor;
- dompet wajib tetap milik pembuat transaksi dan dapat dikelola olehnya;
- kategori wajib tetap milik pembuat transaksi dan sesuai jenis;
- `user_id` transaksi mencatat pengguna yang membuat transaksi;
- edit/hapus memastikan transaksi dibuat oleh pengguna aktif;
- transaksi pada share yang sudah dicabut tidak dapat diedit lagi oleh mantan anggota;
- pemilik buku tidak dapat mengubah transaksi editor karena tidak memiliki akses ke dompet editor.

## 7. Antarmuka Filament

### 7.1 Halaman Buku Kas

- Aktifkan fitur berbagi melalui aksi **Bagikan** pada record buku milik sendiri.
- Pisahkan tampilan menjadi **Buku saya** dan **Dibagikan kepada saya**, atau gunakan badge/kolom **Pemilik** dan **Akses**.
- Sembunyikan aksi edit, hapus, pindahkan saldo, dan jadikan default pada buku bersama.
- Tampilkan badge `Pemilik`, `Editor`, atau `Viewer`.
- Berikan akses cepat ke daftar anggota bagi pemilik.

### 7.2 Pengelolaan anggota

- Ganti input ID mentah dengan select pengguna yang dapat dicari berdasarkan nama atau email.
- Batasi pilihan pada pengguna reguler non-admin yang bukan pemilik.
- Ganti input privilege dengan select `Viewer` dan `Editor`.
- Daftar menampilkan nama buku, nama anggota, email, hak akses, dan tanggal dibagikan.
- Form menyediakan waktu mulai dan waktu berakhir opsional, dengan validasi waktu berakhir harus setelah waktu mulai.
- Daftar menampilkan status `Terjadwal`, `Aktif`, atau `Kedaluwarsa` beserta masa berlakunya.
- Edit hanya dapat mengubah privilege serta menjadwalkan, memperpendek, atau memperpanjang masa aktif.
- Hapus menggunakan konfirmasi **Cabut akses**.
- Halaman/resource tidak boleh menampilkan atau mengubah share buku milik pengguna lain.

### 7.3 Halaman transaksi

- Filter buku kas memuat buku milik sendiri dan buku bersama.
- Viewer dapat melihat transaksi tetapi tidak melihat aksi tambah, edit, hapus, transfer, pindah saldo, atau import.
- Editor dapat mencatat pemasukan/pengeluaran pada buku bersama.
- Editor hanya melihat dompet dan kategori miliknya pada form.
- Aksi edit/hapus hanya tampil untuk transaksi yang dibuat pengguna aktif dan jika akses editor masih berlaku.
- Tampilkan nama pembuat transaksi pada buku bersama.
- Sembunyikan nama dompet pada transaksi yang dibuat pengguna lain.
- Import transaksi ke buku bersama belum didukung pada MVP agar validasi akses batch tetap sederhana.

### 7.4 Laporan dan pencarian

- Buku bersama tersedia pada filter laporan dan pencarian global.
- Viewer dan editor dapat melihat total serta transaksi dari buku bersama.
- Informasi dompet pengguna lain disamarkan, misalnya menjadi `Dompet anggota`.
- Ekspor PDF/XLSX mengikuti aturan penyamaran yang sama.
- Data dari buku lain milik pemilik atau anggota tidak boleh ikut terbaca.

### 7.5 Notifikasi

Kirim notifikasi dalam aplikasi ketika:

- buku dibagikan kepada pengguna;
- privilege diubah;
- akses mendekati tanggal berakhir;
- masa aktif diperpanjang;
- akses dicabut.

Notifikasi berisi nama buku, nama pemilik, dan hak akses tanpa menampilkan saldo atau rincian transaksi.

Pengingat kedaluwarsa disarankan pada H-7 dan H-1. Pengiriman harus idempotent agar satu share tidak menerima notifikasi yang sama berulang kali.

## 8. Interaksi dengan Akun Reguler dan Premium

- Buku bersama tidak dihitung sebagai buku yang dibuat pengguna penerima.
- Menerima share tidak memerlukan Premium.
- Batas jumlah buku milik sendiri tetap mengikuti aturan akun reguler/Premium saat ini.
- Editor dapat bertransaksi pada buku bersama tanpa menjadikan buku tersebut bagian dari kuota miliknya.
- Dompet yang digunakan editor tetap tunduk pada batas dompet reguler/Premium milik editor.
- Pemilik yang masa Premium-nya berakhir tetap dapat membagikan buku utama dan satu buku tambahan gratis; share pada buku lain menjadi hanya-baca sampai Premium aktif kembali atau akses dicabut.

Aturan terakhir perlu ditampilkan dengan jelas pada antarmuka agar anggota memahami mengapa aksi edit dapat hilang.

## 9. Keamanan dan Integritas Data

- Selalu validasi bahwa pemberi share adalah pemilik buku.
- Jangan menerima `buku_kas_id`, `user_id`, atau `privilege` tanpa constraint dan pemeriksaan server.
- Tambahkan unique index untuk mencegah race condition share duplikat.
- Validasi tanggal akhir selalu setelah tanggal mulai dan gunakan zona waktu aplikasi secara konsisten.
- Semua policy dan query wajib memeriksa masa aktif share pada saat request diproses.
- Gunakan database transaction ketika mengubah privilege atau mencabut akses bersama operasi lain.
- Jangan mengekspos nama atau saldo dompet anggota lain.
- Cegah mass assignment privilege selain nilai yang diizinkan.
- Pastikan route langsung ke resource, laporan, atau transaksi tetap menghasilkan `403` atau `404` jika pengguna tidak memiliki akses.
- Catat aktivitas share, perubahan privilege, dan pencabutan akses tanpa mencatat nilai transaksi sensitif.

## 10. Strategi Pengujian

Buat test terarah untuk kelompok `share-buku` dengan cakupan berikut.

### 10.1 Pengelolaan share

- Pemilik dapat membagikan buku miliknya kepada pengguna terdaftar.
- Pemilik tidak dapat membagikan buku pengguna lain.
- Pengguna tidak dapat membagikan buku kepada dirinya sendiri.
- Admin tidak dapat menjadi penerima.
- Share duplikat ditolak oleh validasi dan database constraint.
- Tanggal berakhir sebelum atau sama dengan tanggal mulai ditolak.
- Share terjadwal belum memberikan akses.
- Share tanpa tanggal akhir tetap aktif setelah tanggal mulai.
- Share kedaluwarsa otomatis kehilangan akses baca dan tulis.
- Perpanjangan masa aktif mengembalikan akses tanpa membuat record baru.
- Hanya pemilik dapat mengubah privilege dan mencabut akses.
- Pengguna lain tidak dapat melihat record share melalui route langsung.

### 10.2 Akses buku dan transaksi

- Viewer dan editor dapat melihat buku bersama dan seluruh transaksinya.
- Pengguna tanpa share tidak dapat melihat buku atau transaksi.
- Viewer tidak dapat membuat, mengubah, menghapus, mentransfer, atau mengimpor transaksi.
- Editor dapat membuat transaksi menggunakan dompet dan kategori miliknya.
- Editor tidak dapat memakai dompet atau kategori pemilik maupun anggota lain.
- Editor dapat mengubah dan menghapus transaksi buatannya sendiri.
- Editor tidak dapat mengubah atau menghapus transaksi pemilik atau editor lain.
- Pemilik tidak dapat mengubah transaksi editor jika operasi tersebut memengaruhi dompet editor.
- Pencabutan akses langsung menghentikan akses baca dan tulis editor.
- Perubahan editor menjadi viewer langsung menghentikan akses mutasi.
- Kedaluwarsa saat pengguna sedang membuka halaman tetap ditolak pada request mutasi berikutnya.

### 10.3 Saldo dan laporan

- Transaksi editor memperbarui saldo buku bersama dan dompet editor secara atomik.
- Edit dan hapus transaksi editor mengembalikan kedua saldo dengan benar.
- Saldo dompet anggota lain tidak terlihat.
- Laporan buku bersama menghitung seluruh transaksi buku dengan benar.
- Ekspor laporan menyamarkan dompet anggota lain.
- Filter dan pencarian tidak membocorkan transaksi buku yang tidak dibagikan.

### 10.4 Batas akun

- Buku bersama tidak memengaruhi kuota buku penerima.
- Editor reguler hanya dapat memakai dompet yang masih dapat dikelolanya.
- Berakhirnya Premium pemilik menerapkan pembatasan yang telah ditetapkan tanpa menghapus share atau transaksi.

Gunakan test dengan filter spesifik. Karena proyek memakai Pest 5, jalankan:

```powershell
php artisan test --filter="share buku" --tia
```

Jika Pest menyatakan TIA tidak berlaku untuk partial run, gunakan:

```powershell
php artisan test --filter="share buku" --parallel
```

Jangan menjalankan seluruh test suite di mesin lokal dan hindari `assertSee`.

## 11. Tahapan Implementasi

### Tahap 1 — Fondasi data dan otorisasi

- [ ] Tambahkan unique index serta index query pada `share_buku`.
- [ ] Tambahkan `berlaku_mulai`, `berlaku_sampai`, dan scope masa aktif pada `share_buku`.
- [ ] Lengkapi relasi model buku, pengguna, dan share.
- [ ] Buat resolver hak akses buku yang terpusat.
- [ ] Buat policy untuk buku kas, share, dan transaksi.
- [ ] Tambahkan test kepemilikan, viewer, editor, dan pengguna tanpa akses.

### Tahap 2 — Pengelolaan anggota

- [ ] Ganti form ID mentah dengan pilihan buku dan pengguna yang aman.
- [ ] Tambahkan validasi self-share, admin, dan duplikasi.
- [ ] Tambahkan validasi dan form pengaturan masa aktif.
- [ ] Batasi query resource hanya pada buku milik pengguna.
- [ ] Aktifkan aksi Bagikan dan daftar anggota.
- [ ] Tambahkan ubah privilege, cabut akses, dan notifikasi.
- [ ] Tambahkan status terjadwal, aktif, kedaluwarsa, serta aksi perpanjang.
- [ ] Tambahkan test Filament untuk seluruh aksi anggota.

### Tahap 3 — Akses baca

- [ ] Tampilkan buku bersama pada daftar buku kas.
- [ ] Izinkan viewer/editor membuka transaksi buku bersama.
- [ ] Hubungkan buku bersama ke pencarian dan laporan.
- [ ] Terapkan penyamaran informasi dompet anggota lain.
- [ ] Tambahkan test kebocoran data lintas buku dan lintas dompet.

### Tahap 4 — Akses editor

- [ ] Izinkan editor membuat transaksi menggunakan dompet dan kategorinya sendiri.
- [ ] Batasi edit/hapus pada transaksi buatan editor sendiri.
- [ ] Pastikan service memperbarui saldo buku bersama dan dompet editor secara atomik.
- [ ] Nonaktifkan transfer dan import pada buku bersama.
- [ ] Tambahkan test saldo, concurrency, pencabutan akses, dan perubahan privilege.

### Tahap 5 — Dokumentasi dan verifikasi

- [ ] Perbarui `FITUR_APLIKASI.md` setelah implementasi aktif.
- [ ] Jalankan formatter pada file yang berubah.
- [ ] Jalankan test lokal dengan filter dan flag yang diwajibkan.
- [ ] Lakukan smoke test untuk pemilik, editor, viewer, dan pengguna tanpa akses.
- [ ] Pastikan full suite dijalankan oleh CI.

## 12. Kriteria Penerimaan

Fitur dianggap selesai ketika:

- pemilik dapat menambah, mengubah privilege, dan mencabut anggota tanpa memakai ID mentah;
- viewer hanya dapat membaca buku, transaksi, dan laporan yang dibagikan;
- editor dapat membuat serta mengelola transaksi miliknya pada buku bersama;
- editor hanya dapat memakai dompet dan kategori miliknya;
- informasi dompet anggota lain tidak terekspos;
- seluruh route dan service menegakkan hak akses di sisi server;
- transaksi editor memperbarui saldo buku dan dompet secara konsisten;
- share tidak mengubah kuota buku penerima;
- perubahan privilege dan pencabutan akses berlaku segera;
- share terjadwal, aktif, tanpa batas waktu, dan kedaluwarsa mengikuti waktu server secara konsisten;
- test akses, saldo, laporan, pencarian, dan antarmuka lulus;
- `FITUR_APLIKASI.md` telah sesuai dengan implementasi aktif.

## 13. Pengembangan Lanjutan

Setelah MVP stabil, fitur dapat diperluas dengan:

- undangan melalui email untuk pengguna yang belum terdaftar;
- penerimaan atau penolakan undangan;
- pengingat masa aktif yang dapat dikonfigurasi;
- histori perubahan privilege dan pencabutan akses;
- akses granular seperti hanya melihat laporan atau hanya menambah transaksi;
- transfer saldo pada buku bersama dengan persetujuan pemilik;
- import transaksi oleh editor;
- komentar atau catatan aktivitas per transaksi;
- pemberitahuan transaksi baru kepada pemilik;
- kepemilikan bersama atau pemindahan pemilik buku.
