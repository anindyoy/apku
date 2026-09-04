# Rencana Implementasi Fitur Dompet

## Status implementasi

Fitur utama telah diimplementasikan, meliputi skema dan backfill, model dan relasi, onboarding, lifecycle transaksi melalui service domain, transfer dompet dan buku kas, pembatasan masa aktif, penghapusan dompet dengan pemindahan saldo, filter transaksi, pencarian, laporan, PDF, Excel, factory, dan seeder.

`TransaksiObserver` dipertahankan hanya sebagai jalur kompatibilitas untuk penulisan model secara langsung. Alur aplikasi menggunakan service domain dengan event model dinonaktifkan agar saldo tidak dihitung dua kali.

Pekerjaan audit integritas dan rekonsiliasi tidak termasuk penyelesaian fitur utama ini dan ditunda ke tugas terpisah sesuai keputusan pengguna. Bagian audit dalam dokumen ini tetap menjadi referensi untuk tugas tersebut.

## Ringkasan kebutuhan

Fitur ini memisahkan dua dimensi pencatatan keuangan:

- **Buku kas** menjadi kelompok atau tujuan pencatatan transaksi.
- **Dompet** menjadi lokasi uang sebenarnya, misalnya Cash, rekening bank, atau e-wallet.
- Setiap pengguna wajib mempunyai minimal satu buku kas dan satu dompet.
- Data awal pengguna baru adalah buku kas berlabel **Kas Utama** dan dompet berlabel **Cash**.
- Setiap transaksi wajib terkait dengan tepat satu buku kas dan satu dompet milik pengguna yang sama.
- Pengguna dapat memindahkan saldo dari satu dompet ke dompet lain tanpa mengubah saldo bersih buku kas.
- Pengguna dengan masa aktif valid atau role super dapat menggunakan seluruh dompetnya.
- Pengguna dengan masa aktif tidak valid hanya dapat menggunakan maksimal dua dompet untuk transaksi biasa dan sebagai tujuan transfer. Dompet ke-3 dan seterusnya tetap tersimpan dan boleh menjadi sumber transfer agar saldonya dapat dikeluarkan, tetapi tidak dapat dipakai untuk membuat atau mengubah transaksi biasa sampai masa aktif kembali valid.
- Masa aktif yang tidak valid tidak menonaktifkan fitur transfer. Pengguna tetap dapat melakukan transfer saldo antar buku kas dan antar dompet yang termasuk kuota gratis serta dapat dikelola.

Keputusan produk yang sudah disepakati:

- Saldo awal onboarding menjadi saldo dompet **Cash** dan dicatat pada buku kas **Kas Utama** dalam transaksi yang sama.
- Pengguna boleh mengganti nama Kas Utama dan Cash; status default tetap melekat pada record dan tidak bergantung pada nama.
- Jumlah dompet mengikuti pembatasan langganan buku kas: dua dompet dapat digunakan tanpa masa aktif yang valid, sedangkan seluruh dompet dapat digunakan ketika masa aktif valid.
- Pada transfer antar buku kas, dompet asal dan dompet tujuan boleh sama karena buku kas dan dompet adalah dimensi pencatatan yang berbeda.
- Transfer antar-dompet tetap diperbolehkan ketika saldo dompet asal tidak mencukupi. Saldo boleh menjadi negatif dan ditandai dengan warna berbeda pada daftar transaksi.

## Kondisi aplikasi saat ini

- Saldo disimpan pada `buku_kas.saldo` dan diperbarui oleh `TransaksiObserver` serta beberapa callback Filament.
- Tabel `transaksi` sudah mewajibkan `buku_kas_id`, tetapi belum mempunyai `dompet_id`.
- Onboarding meminta pengguna membuat buku kas pertama dan membuat transaksi “Saldo awal”.
- Transfer antar buku kas direpresentasikan oleh dua transaksi dengan `transfer_code` yang sama.
- Filter transaksi, widget saldo, laporan, seeder, factory, dan cukup banyak pengujian masih hanya mengenal buku kas.
- Penghapusan buku kas memindahkan transaksi dan saldo ke buku kas lain. Pola yang setara perlu tersedia untuk dompet.

## Keputusan desain yang disarankan

### Model data

Buat tabel `dompet` dengan kolom:

| Kolom | Aturan |
| --- | --- |
| `id` | primary key |
| `user_id` | foreign key ke `users`, cascade update/delete |
| `nama_dompet` | string maksimal 50 karakter |
| `saldo` | integer/big integer, default 0 |
| `is_default` | boolean, default false |
| `description` | string maksimal 200, nullable |
| `deleted_at` | nullable, untuk soft delete dan mempertahankan histori |
| timestamps | `created_at` dan `updated_at` |

Tambahkan constraint unik `(user_id, nama_dompet)` dan indeks `(user_id, is_default)`. Gunakan aturan aplikasi dan transaksi database untuk memastikan hanya satu dompet default per pengguna. Bila database produksi mendukung partial unique index, constraint tersebut dapat diperkuat di tingkat database.

Tambahkan `is_default` pada `buku_kas` agar buku utama tidak lagi ditentukan dari nama atau ID tertua. Tambahkan unique `(user_id, nama_buku)` jika belum tercakup oleh migration yang sudah tersedia.

Tambahkan `dompet_id` pada `transaksi` sebagai foreign key ke `dompet`. Kondisi akhir kolom harus `NOT NULL` dan menggunakan `restrictOnDelete()` agar histori transaksi tetap menunjuk ke dompet asal yang dihapus secara lunak dan tidak terhapus tanpa proses eksplisit.

Perluas nilai `transaksi.jenis` dengan **Transfer Dompet Pengeluaran** dan **Transfer Dompet Pemasukan**, atau ganti enum database dengan string yang divalidasi melalui PHP enum agar penambahan jenis transaksi berikutnya tidak membutuhkan perubahan enum database yang berisiko. Pasangan transfer dompet menggunakan `transfer_code` yang sama.

### Aturan saldo

- Setiap pemasukan menambah saldo buku kas dan saldo dompet.
- Setiap pengeluaran mengurangi saldo buku kas dan saldo dompet.
- Perubahan nominal, buku kas, dompet, atau jenis transaksi harus membalik dampak nilai lama lalu menerapkan dampak nilai baru secara atomik.
- Penghapusan transaksi harus membalik saldo buku kas dan dompet.
- Transfer dompet membuat dua transaksi pada buku kas yang sama: sisi pengeluaran mengurangi dompet asal dan sisi pemasukan menambah dompet tujuan. Dampak keduanya pada buku kas saling meniadakan.
- Semua operasi saldo memakai `DB::transaction()` dan penguncian baris (`lockForUpdate`) untuk mencegah saldo hilang akibat transaksi bersamaan.
- Kolom saldo diperlakukan sebagai cache yang dapat direkonsiliasi dari histori transaksi. Sediakan service/command rekonsiliasi agar perbedaan saldo dapat dideteksi dan diperbaiki.

Pembaruan saldo sebaiknya dipusatkan dalam service domain, misalnya `TransaksiSaldoService`. Hindari mempertahankan logika saldo yang tersebar di observer, action edit/hapus, onboarding, dan seeder.

### Aturan kepemilikan

Pada create dan update transaksi, validasi bahwa:

1. `transaksi.user_id` sama dengan pengguna aktif atau pemilik transaksi;
2. buku kas dimiliki pengguna tersebut;
3. dompet dimiliki pengguna tersebut;
4. untuk transfer, kedua buku kas dan dompet pada pasangan transaksi juga dimiliki pengguna yang sama;
5. transfer dompet memakai dua dompet berbeda yang keduanya sedang dapat dikelola berdasarkan masa aktif pengguna.

Validasi ini harus ada di lapisan aplikasi/domain, bukan hanya pada pilihan yang ditampilkan oleh form Filament.

### Batas dompet berdasarkan masa aktif

Terapkan aturan yang konsisten dengan pembatasan buku kas pada `User`:

- User super atau pengguna dengan `masaAktifBerlaku()` bernilai true dapat membuat dan menggunakan semua dompet miliknya.
- Pengguna dengan masa aktif tidak valid hanya dapat membuat dompet selama jumlah dompetnya kurang dari dua.
- Pengguna dengan masa aktif tidak valid tetap boleh melakukan transfer saldo antara dua dompet gratis yang dapat dikelola; jangan menyembunyikan atau menonaktifkan action transfer hanya karena masa aktif berakhir.
- Dua dompet gratis yang tetap aktif adalah dompet default dan satu dompet tambahan dengan ID paling kecil. Pemilihan ini harus deterministik.
- Jika pengguna sebelumnya mempunyai lebih dari dua dompet, dompet ke-3 dan seterusnya berstatus **terbatas** secara terhitung. Record, saldo, dan histori transaksinya tidak dihapus atau dipindahkan otomatis.
- Dompet terbatas masih boleh terlihat pada daftar dompet, laporan, dan histori dengan badge atau keterangan **Tidak aktif—perpanjang masa aktif untuk menggunakan**.
- Dompet terbatas tidak muncul sebagai opsi pada form transaksi baru atau sebagai tujuan transfer. Dompet tersebut tetap muncul sebagai sumber transfer agar saldonya dapat dikeluarkan. Upaya mengirim ID dompet terbatas sebagai tujuan atau sebagai dompet transaksi biasa harus ditolak di lapisan domain/otorisasi.
- Transaksi lama pada dompet terbatas tetap dapat dibaca, tetapi tidak dapat diedit atau dihapus apabila operasi tersebut mengubah saldo dompet. Hal ini mengikuti prinsip pembatasan pengelolaan buku kas saat ini.
- Ketika masa aktif kembali valid, seluruh dompet otomatis dapat digunakan lagi. Karena itu, status terbatas tidak perlu disimpan sebagai flag permanen di database.

Tambahkan helper pada model `User`, misalnya `idDompetUtama()`, `idDompetTambahanGratis()`, `dapatMembuatDompet()`, dan `dapatMengelolaTransaksiPadaDompet(Dompet $dompet)`. Seluruh resource dan service menggunakan helper tersebut agar aturan tidak tersebar.

## Tahapan implementasi

### 1. Tambahkan skema secara kompatibel

- Buat migration tabel `dompet`.
- Tambahkan `is_default` pada `buku_kas` dengan nilai awal `false`.
- Tambahkan `dompet_id` nullable terlebih dahulu pada `transaksi` beserta indeks dan foreign key.
- Jangan langsung menjadikan `dompet_id` wajib sebelum data lama selesai dimigrasikan.
- Gunakan tipe nominal yang sama untuk `buku_kas.saldo`, `dompet.saldo`, dan `transaksi.nominal`; pertimbangkan `bigInteger` bila nominal dapat melewati batas integer 32-bit.

### 2. Migrasikan data lama

Jalankan backfill yang idempoten dan aman di dalam transaksi database per pengguna:

- Pilih buku kas tertua sebagai default. Jika pengguna belum mempunyai buku kas, buat **Kas Utama** dengan saldo 0.
- Buat dompet **Cash** untuk setiap pengguna yang belum mempunyai dompet.
- Isi seluruh `transaksi.dompet_id` lama dengan dompet Cash milik pemilik transaksi.
- Hitung saldo awal dompet Cash dari dampak seluruh histori transaksi pengguna. Sebelum deployment, bandingkan hasilnya dengan total `buku_kas.saldo` dan catat selisih untuk audit.
- Jika histori lama tidak dapat merekonstruksi saldo yang tersimpan, buat transaksi penyesuaian yang eksplisit; jangan mengubah angka diam-diam.
- Setelah verifikasi tidak ada `dompet_id` bernilai null, ubah kolom menjadi `NOT NULL`.

Backfill sebaiknya berupa command terpisah yang dapat dijalankan ulang dan menampilkan jumlah pengguna, dompet, transaksi yang diperbarui, serta selisih saldo. Migration skema tetap singkat agar deployment tidak terkunci terlalu lama.

### 3. Tambahkan model dan relasi

- Buat model dan factory `Dompet` dengan global scope pengguna mengikuti pola `BukuKas`.
- Tambahkan relasi `User::dompet()`, `Dompet::transaksi()`, dan `Transaksi::dompet()`.
- Tambahkan helper untuk mengambil buku kas dan dompet default berdasarkan `is_default`.
- Tambahkan helper akses dompet yang membatasi pengguna bermasa aktif tidak valid ke dompet default dan satu dompet tambahan gratis.
- Jangan gunakan label “Cash” atau “Kas Utama” sebagai penentu record default dalam query.

### 4. Pastikan data default pengguna

- Ubah onboarding agar selalu membuat buku kas **Kas Utama** dan dompet **Cash** secara atomik.
- Saldo awal dicatat sebagai transaksi yang mempunyai kedua foreign key tersebut.
- Sesuaikan middleware onboarding: pengguna dianggap selesai hanya jika mempunyai minimal satu buku kas dan satu dompet.
- Tambahkan mekanisme idempoten untuk akun yang dibuat di luar onboarding, misalnya service `PastikanAkunKeuanganDefault`, dan panggil dari seluruh jalur pembuatan user yang relevan.
- Hindari observer user yang melakukan query sebelum transaksi pembuatan user selesai kecuali benar-benar diperlukan; service eksplisit lebih mudah diuji dan dikendalikan.

Form onboarding menampilkan **Kas Utama** dan **Cash** sebagai nilai awal. Pengguna dapat mengganti nama tersebut saat onboarding maupun setelahnya, sedangkan atribut `is_default` tetap melekat pada record yang dibuat.

### 5. Pusatkan lifecycle transaksi dan saldo

- Pindahkan proses create, update, delete, dan transfer ke service domain tunggal.
- Pada create, service mengisi `user_id`, memvalidasi kepemilikan, menyimpan transaksi, lalu memperbarui kedua saldo.
- Pada update, service menangani perubahan nominal, jenis, buku kas, dan dompet, bukan hanya perubahan nominal seperti observer saat ini.
- Pada delete, service membalik dampak saldo sebelum menghapus transaksi.
- Untuk transfer antar buku kas, pertahankan pasangan transaksi dan `transfer_code`, tetapi setiap sisi wajib membawa `dompet_id` yang sesuai. Dompet asal dan tujuan boleh merupakan dompet yang sama; dalam kondisi ini saldo bersih dompet tersebut tidak berubah.
- Untuk transfer antar dompet, buat pasangan **Transfer Dompet Pengeluaran** dan **Transfer Dompet Pemasukan** dengan buku kas yang sama pada kedua sisi.
- Gunakan UUID untuk kode transfer agar tidak bergantung pada `uniqid()`.
- Pastikan pasangan transfer dibuat, diubah, dan dihapus dalam satu transaksi database.

### 6. Tambahkan fitur pindah saldo antar-dompet

- Tambahkan action **Pindah Saldo Dompet** pada halaman transaksi atau resource dompet.
- Action tersedia untuk pengguna dengan masa aktif valid maupun tidak valid. Masa aktif hanya menentukan dompet mana yang dapat dipilih, bukan apakah fitur transfer tersedia.
- Form meminta `dompet_asal_id`, `dompet_tujuan_id`, `buku_kas_id`, `nominal`, `tanggal`, dan deskripsi opsional.
- Dompet tujuan harus berbeda dari dompet asal. Kedua select hanya menampilkan dompet yang dapat dikelola pengguna.
- Buku kas berfungsi sebagai konteks pencatatan dan harus sama pada kedua transaksi pasangan agar saldo bersih buku kas tidak berubah.
- Service mengunci dompet asal, dompet tujuan, dan buku kas dengan urutan ID yang konsisten untuk mengurangi risiko deadlock.
- Service membuat dua record dengan UUID `transfer_code` yang sama dalam satu transaksi database:
  - sisi asal: jenis **Transfer Dompet Pengeluaran**, buku kas terpilih, dan dompet asal;
  - sisi tujuan: jenis **Transfer Dompet Pemasukan**, buku kas terpilih, dan dompet tujuan.
- Setelah transfer, saldo dompet asal berkurang dan saldo dompet tujuan bertambah dengan nominal yang sama, sedangkan saldo buku kas kembali ke nilai semula.
- Edit transfer harus memperbarui kedua sisi secara atomik. Minimal dukung perubahan nominal, tanggal, dan deskripsi; jika sumber, tujuan, atau buku kas dapat diubah, service wajib membalik pasangan lama sebelum menerapkan pasangan baru.
- Menghapus salah satu sisi harus menghapus pasangan transfer dan membalik kedua saldo secara atomik.
- Tampilkan transfer sebagai satu aktivitas logis pada UI detail atau beri tautan ke transaksi pasangannya agar pengguna tidak mengira ada pemasukan/pengeluaran riil.
- Transfer dompet tidak dihitung sebagai pemasukan atau pengeluaran pada ringkasan laba-arus pengguna. Nilainya hanya memengaruhi mutasi dan saldo per dompet.
- Jangan menolak transfer ketika nominal melebihi saldo dompet asal. Simpan hasil saldo negatif dan tandai baris atau nilai saldo dompet negatif dengan warna kontras pada daftar transaksi.
- Pengguna dengan masa aktif tidak valid boleh memilih dompet ke-3 dan seterusnya sebagai asal transfer untuk mengeluarkan saldo, tetapi tidak sebagai tujuan. Transfer lama pada dompet terbatas tetap dapat dilihat, tetapi tidak dapat diubah atau dihapus sampai akses kembali aktif.
- Untuk transfer antar buku kas, terapkan prinsip yang sama: pengguna bermasa aktif tidak valid tetap dapat transfer selama buku kas asal dan tujuan termasuk buku kas gratis yang dapat dikelola.

### 7. Tambahkan pengelolaan dompet di Filament

- Buat `DompetResource` untuk daftar, tambah, ubah, dan hapus dompet.
- Tampilkan nama, saldo, status default, dan deskripsi.
- Tampilkan status akses setiap dompet. Dompet di luar kuota dua dompet diberi badge terbatas ketika masa aktif pengguna tidak valid.
- Tambahkan aksi menjadikan dompet sebagai default secara atomik.
- Cegah penghapusan dompet terakhir.
- Ganti aksi hapus biasa dengan action **Pindahkan Saldo & Hapus Dompet**. Form wajib meminta dompet tujuan dan buku kas pencatatan, serta menampilkan saldo yang akan dipindahkan.
- Dompet tujuan harus berbeda, masih aktif, dapat dikelola berdasarkan masa aktif, dan dimiliki pengguna yang sama.
- Buku kas pencatatan diisi awal dengan Kas Utama, harus dapat dikelola pengguna, dan dipakai pada kedua sisi transfer agar saldo bersih buku kas tidak berubah.
- Jalankan pemindahan saldo dan soft delete dalam satu `DB::transaction()` dengan `lockForUpdate()` pada kedua dompet.
- Jika saldo dompet asal positif, buat transfer keluar dari dompet asal dan transfer masuk ke dompet tujuan sebesar saldo tersebut.
- Jika saldo dompet asal negatif, pindahkan kewajiban secara terbalik: saldo dompet asal dinaikkan sampai nol dan saldo dompet tujuan dikurangi sebesar nilai absolutnya. Pasangan transaksi tetap menyimpan nilai nominal positif dan arah transaksi yang sesuai.
- Jika saldo dompet asal nol, tidak perlu membuat transaksi transfer; langsung lanjutkan proses soft delete.
- Setelah pemindahan berhasil, saldo dompet asal harus tepat nol, lalu lakukan soft delete. Jangan memindahkan atau menulis ulang `dompet_id` transaksi lama karena akan merusak histori dan saldo berjalan.
- Transaksi pemindahan saldo menyimpan `transfer_code`, deskripsi sistem seperti **Pemindahan saldo sebelum penghapusan dompet**, serta referensi pasangan agar dapat diaudit.
- Dompet default tidak dapat dihapus. Pengguna harus menjadikan dompet lain sebagai default terlebih dahulu sebelum menjalankan penghapusan.
- Dompet yang sudah dihapus secara lunak tidak muncul pada pilihan transaksi/filter normal, tetapi namanya tetap dapat ditampilkan pada histori lama. Sediakan filter **Termasuk Dompet Dihapus** bila histori perlu dicari secara khusus.
- Hard delete hanya boleh dilakukan oleh proses administratif ketika dompet tidak mempunyai referensi transaksi sama sekali.
- Pengguna bermasa aktif tidak valid hanya dapat membuat dompet jika jumlahnya masih kurang dari dua. Tampilkan ajakan memperpanjang masa aktif ketika batas tercapai.
- Sembunyikan atau nonaktifkan aksi edit, hapus, dan pemindahan saldo untuk dompet terbatas, serta ulangi validasinya di server agar tidak dapat dilewati lewat request manual.

### 8. Integrasikan dompet ke transaksi dan laporan

- Tambahkan select **Dompet** yang wajib pada form pemasukan, pengeluaran, edit, dan transfer.
- Isi default select dari dompet `is_default`; jangan mengambil record pertama secara implisit.
- Batasi opsi select ke dompet yang dapat dikelola oleh pengguna berdasarkan masa aktifnya.
- Tambahkan filter **Dompet** pada daftar transaksi dengan opsi **Semua Dompet** dan seluruh dompet milik pengguna, termasuk dompet terbatas untuk kebutuhan melihat histori.
- Ketika satu dompet dipilih, query hanya menampilkan transaksi dengan `dompet_id` tersebut. Opsi **Semua Dompet** tidak menambahkan kondisi `dompet_id`.
- Filter dompet dapat digunakan bersamaan dengan filter buku kas. Jika keduanya dipilih, query menggunakan kondisi `buku_kas_id` dan `dompet_id` sekaligus.
- Jangan menerima ID dompet pengguna lain dari query string atau state Livewire. Jika nilai filter tidak dimiliki pengguna aktif, kembalikan hasil kosong atau reset ke **Semua Dompet** secara konsisten.
- Pertahankan pilihan filter saat pengguna berpindah halaman pagination atau kembali dari halaman detail/edit, mengikuti pola filter buku kas yang sudah ada.
- Pada mode **Semua Dompet**, tampilkan kolom nama dompet pada tabel. Ketika satu dompet dipilih, kolom nama dompet dapat disembunyikan dan diganti dengan saldo berjalan dompet.
- Tambahkan filter dompet yang setara pada halaman pencarian transaksi.
- Perbarui saldo berjalan agar dapat dihitung per buku kas, per dompet, atau kombinasi keduanya. Query window saat ini hanya mempartisi berdasarkan `buku_kas_id`.
- Tampilkan saldo berjalan dompet pada konteks filter dompet. Gunakan warna negatif/danger ketika nilainya di bawah nol dan warna normal ketika nol atau positif; jangan hanya mengandalkan warna, sertakan tanda minus dan label/tooltip yang dapat dipahami pengguna serta aksesibel.
- Tambahkan ringkasan saldo semua dompet pada widget. Hindari menjumlahkan saldo buku kas dan dompet menjadi satu total karena keduanya adalah dua tampilan atas uang yang sama.
- Tambahkan filter dompet dan label dompet pada laporan layar, ekspor, dan PDF.
- Pastikan laporan lama tetap dapat difilter berdasarkan buku kas tanpa menggandakan nominal.
- Pisahkan transfer dompet dari pemasukan dan pengeluaran pada query laporan; tampilkan sebagai mutasi antar-dompet bila diperlukan.

### 9. Perbarui seeder, factory, dan data pengujian

- Buat `DompetFactory`.
- Perbarui `TransaksiFactory`, `TransaksiSeeder`, `UserTransactionSeeder`, helper test Filament, dan seluruh fixture manual agar mengisi `dompet_id`.
- Pastikan bulk insert seeder menghitung dan memperbarui saldo buku kas serta dompet secara konsisten.
- Semua komentar kode baru atau yang disentuh ditulis dalam Bahasa Indonesia sesuai aturan proyek.

### 10. Tambahkan command audit dan rekonsiliasi

Sediakan command dengan mode pemeriksaan sebagai default dan opsi perbaikan eksplisit. Pemeriksaan minimal meliputi:

- pengguna tanpa buku kas atau dompet;
- pengguna dengan lebih dari satu default;
- transaksi tanpa buku kas/dompet atau dengan kepemilikan silang;
- pasangan transfer yang tidak lengkap;
- pasangan transfer dompet dengan nominal, buku kas, pengguna, atau `transfer_code` yang tidak konsisten;
- saldo tersimpan yang berbeda dari hasil agregasi transaksi.

Command harus menghasilkan ringkasan yang aman dibaca saat deployment dan exit code non-zero bila ditemukan pelanggaran integritas.

## Rencana pengujian terarah

Ikuti aturan proyek: periksa versi Pest terlebih dahulu, gunakan `--tia` bila Pest v5+, atau `--parallel` untuk versi sebelumnya. Jangan menjalankan seluruh suite lokal dan jangan memakai `assertSee`.

Tambahkan atau sesuaikan pengujian terfokus berikut:

- migration/backfill membuat satu Cash per pengguna dan mengisi semua transaksi lama;
- onboarding membuat Kas Utama, Cash, dan transaksi saldo awal yang terhubung ke keduanya;
- middleware menolak pengguna yang kehilangan salah satu entitas wajib;
- relasi dan global scope dompet tidak membocorkan data pengguna lain;
- create pemasukan/pengeluaran memperbarui kedua saldo;
- update nominal, jenis, buku kas, dan dompet membalik serta menerapkan saldo dengan benar;
- delete transaksi dan delete pasangan transfer membalik kedua saldo;
- pindah saldo dompet membuat dua transaksi berpasangan, mengurangi saldo asal, menambah saldo tujuan, dan tidak mengubah saldo buku kas;
- transfer dompet menolak dompet asal dan tujuan yang sama, dompet pengguna lain, serta dompet yang sedang terbatas;
- transfer dompet dengan nominal melebihi saldo asal tetap berhasil dan menghasilkan saldo dompet negatif;
- daftar transaksi memberi penanda warna danger serta nilai minus pada saldo dompet yang negatif, tanpa menandai saldo nol atau positif;
- transfer antar buku kas menerima dompet asal dan tujuan yang sama serta menghasilkan perubahan bersih dompet sebesar nol;
- edit dan delete transfer dompet memproses kedua sisi secara atomik;
- laporan pemasukan/pengeluaran tidak memasukkan transfer antar-dompet sebagai pendapatan atau biaya;
- transaksi menolak buku kas atau dompet milik pengguna lain;
- form transaksi mewajibkan dan memilih dompet default;
- filter daftar transaksi berdasarkan satu dompet hanya menampilkan transaksi dompet tersebut;
- opsi Semua Dompet menampilkan seluruh transaksi pengguna dan kolom nama dompet;
- filter dompet dan buku kas dapat dikombinasikan tanpa menampilkan transaksi di luar kedua pilihan;
- filter dompet menolak atau mengabaikan ID dompet milik pengguna lain;
- pilihan filter dompet bertahan selama pagination dan navigasi yang relevan;
- pengguna dengan masa aktif tidak valid dapat membuat dan menggunakan maksimal dua dompet;
- pengguna dengan masa aktif tidak valid tetap dapat membuka dan menjalankan action transfer antar dua dompet atau buku kas gratis yang dapat dikelola;
- masa aktif tidak valid tidak boleh menjadi satu-satunya alasan penolakan transfer;
- dompet ke-3 dan seterusnya milik pengguna dengan masa aktif tidak valid tetap tersimpan dan terlihat, tidak dapat dipakai pada create, edit, atau delete transaksi, tetapi dapat menjadi sumber transfer untuk mengeluarkan saldo;
- request manual yang memakai dompet terbatas sebagai tujuan transfer atau dompet transaksi biasa ditolak meskipun melewati pembatasan UI;
- seluruh dompet kembali dapat digunakan setelah masa aktif diperpanjang;
- user super tidak terkena batas jumlah atau penggunaan dompet;
- penghapusan dompet terakhir ditolak;
- penghapusan dompet bersaldo positif memindahkan seluruh saldo ke dompet tujuan, menyisakan saldo asal nol, lalu melakukan soft delete;
- penghapusan dompet bersaldo negatif memindahkan kewajiban ke dompet tujuan dan menyisakan saldo asal nol;
- penghapusan dompet bersaldo nol tidak membuat transfer yang tidak diperlukan;
- penghapusan dompet default ditolak;
- transaksi lama tetap menunjuk ke dompet asal setelah soft delete dan nama dompet masih dapat ditampilkan pada histori;
- kegagalan pemindahan saldo membatalkan seluruh proses sehingga dompet tidak terhapus sebagian;
- daftar, pencarian, widget, laporan, ekspor, dan PDF menghormati filter dompet;
- command audit mendeteksi data invalid dan mode perbaikan bersifat idempoten;
- seeder menghasilkan transaksi dengan `buku_kas_id` dan `dompet_id` valid.

Jalankan hanya file atau filter test yang berkaitan dengan perubahan pada setiap tahap, misalnya test onboarding setelah tahap 4 dan test lifecycle transaksi setelah tahap 5.

## Strategi deployment

Gunakan deployment bertahap agar aplikasi lama dan baru tetap kompatibel:

1. Deploy tabel dompet, flag default, dan `transaksi.dompet_id` nullable.
2. Jalankan backfill dan command audit; simpan hasil audit.
3. Deploy kode yang selalu menulis dan membaca `dompet_id`.
4. Pantau transaksi baru dan rekonsiliasi saldo.
5. Setelah tidak ada nilai null atau pelanggaran kepemilikan, deploy constraint `NOT NULL`.
6. Hapus jalur kompatibilitas sementara setelah masa observasi.

Siapkan backup database dan prosedur rollback sebelum backfill. Rollback kode tidak boleh menghapus tabel/kolom dompet selama masih ada aplikasi versi baru yang berjalan.

## Kriteria penerimaan

- Setiap pengguna mempunyai setidaknya satu buku kas dan satu dompet.
- Pengguna baru memperoleh buku kas default Kas Utama dan dompet default Cash.
- Tidak ada transaksi tanpa `buku_kas_id` dan `dompet_id`.
- Buku kas dan dompet pada transaksi selalu dimiliki pengguna transaksi yang sama.
- Create, update, delete, dan transfer menjaga saldo buku kas dan dompet secara atomik.
- Pindah saldo antar-dompet mengubah kedua saldo dompet dengan nominal yang sama tanpa mengubah saldo bersih buku kas atau laporan pemasukan/pengeluaran.
- Saldo dompet boleh negatif; kondisi negatif terlihat jelas dan aksesibel pada daftar transaksi.
- Data pengguna lama berhasil di-backfill tanpa kehilangan histori.
- Dompet default, dompet terakhir, dan buku kas terakhir tidak dapat dihapus.
- Dompet yang dihapus memindahkan saldo atau kewajibannya ke dompet tujuan melalui transaksi yang dapat diaudit, kemudian dihapus secara lunak tanpa mengubah referensi histori lama.
- Pengguna dengan masa aktif tidak valid hanya dapat membuat dan menggunakan dua dompet: dompet default dan satu dompet tambahan gratis.
- Pengguna dengan masa aktif tidak valid tetap dapat mentransfer saldo antar buku kas atau dompet yang berada dalam kuota gratis dan dapat dikelola.
- Dompet di luar kuota tidak kehilangan data dan otomatis aktif kembali setelah masa aktif valid.
- Pembatasan dompet ditegakkan pada UI, otorisasi, dan service transaksi.
- UI transaksi, pencarian, widget, laporan, ekspor, dan PDF menampilkan serta memfilter dompet dengan benar.
- Daftar transaksi menyediakan filter Semua Dompet atau satu dompet, dapat dikombinasikan dengan filter buku kas, dan tidak membocorkan data pengguna lain.
- Audit integritas tidak menemukan foreign key kosong, kepemilikan silang, pasangan transfer rusak, atau selisih saldo.
- Seluruh test terarah lulus sesuai kebijakan testing proyek.

## Status keputusan produk

Seluruh keputusan produk yang teridentifikasi dalam ruang lingkup rencana ini sudah dikonfirmasi. Implementasi dapat dimulai tanpa keputusan terbuka tambahan.
