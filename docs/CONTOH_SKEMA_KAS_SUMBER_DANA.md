# Contoh Skema Penggunaan Kas dan Sumber Dana

Dokumen ini merangkum contoh skenario bertahap sebagai bahan penyusunan tutorial pengguna di [`resources/content/tutorial.json`](../resources/content/tutorial.json). Rujukan fitur ada di [`FITUR_APLIKASI.md`](../FITUR_APLIKASI.md); jika terdapat perbedaan, source code dan test menjadi sumber kebenaran.

## Konsep Dasar

- **Kas** adalah buku pencatatan: untuk apa uang dipakai.
- **Sumber Dana** adalah tempat uang berada: Tunai, rekening bank, e-wallet, atau Lainnya.
- Setiap transaksi memilih kas dan sumber dana, sehingga total semua kas selalu sama dengan total semua sumber dana.
- Transfer kas hanya mengubah saldo kas (sumber dana pencatatan ditentukan otomatis), sedangkan pemindahan saldo sumber dana hanya mengubah saldo sumber dana (kas pencatatan ditentukan otomatis).

## Ringkasan Skenario

| Skenario | Tokoh | Tipe akun | Fokus |
|---|---|---|---|
| A. Dasar | Rina | Reguler | 1 sampai 2 kas, pencatatan, transfer, Cocokkan Saldo |
| B. Banyak kas (sistem amplop) | Rina | Premium | Kas per pos, Kas Tabungan, transfer antar-kas |
| C. Kas bersama | Rina (pemilik), Budi (Editor), Ibu (Viewer) | Pemilik Premium | Kolaborator Kas, pembagian hak akses |

---

## Skenario A: Dasar (akun Reguler)

### Tahap 1: Pengaturan awal
Rina membuat kas utama **Harian** dan sumber dana utama **Tunai** dengan saldo awal Rp 1.000.000.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 1.000.000 | Tunai | 1.000.000 |

### Tahap 2: Menambah sumber dana dan mencatat pemasukan
Rina menambah sumber dana **BCA** (saldo awal 0), lalu mencatat pemasukan gaji Rp 5.000.000 dengan kas Harian, sumber dana BCA, dan kategori Gaji.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 6.000.000 | Tunai | 1.000.000 |
| | | BCA | 5.000.000 |

### Tahap 3: Mencatat pengeluaran
Makan Rp 50.000 (Tunai) dan listrik Rp 300.000 (BCA), keduanya di kas Harian.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 5.650.000 | Tunai | 950.000 |
| | | BCA | 4.700.000 |

### Tahap 4: Memindahkan saldo antar-sumber dana
Rina menarik tunai Rp 500.000 dari BCA ke Tunai. Saldo kas tidak berubah, hanya posisi uangnya yang pindah.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 5.650.000 | Tunai | 1.450.000 |
| | | BCA | 4.200.000 |

### Tahap 5: Kas kedua dan transfer antar-kas
Rina membuat kas **Usaha**, lalu mentransfer Rp 1.000.000 dari Harian ke Usaha. Saldo per sumber dana tidak berubah.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 4.650.000 | Tunai | 1.450.000 |
| Usaha | 1.000.000 | BCA | 4.200.000 |

### Tahap 6: Cocokkan Saldo
Uang tunai fisik Rp 1.400.000, sedangkan catatan APKu Rp 1.450.000. Selisih **-Rp 50.000** otomatis dicatat sebagai transaksi **Penyesuaian Saldo** (pengeluaran tanpa kategori) pada kas yang dipilih, misalnya Harian. Penghitung pecahan uang hanya muncul untuk sumber dana jenis Tunai.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Harian | 4.600.000 | Tunai | 1.400.000 |
| Usaha | 1.000.000 | BCA | 4.200.000 |
| **Total** | **5.600.000** | **Total** | **5.600.000** |

### Batas akun Reguler
Pada akhir skenario kuota gratis sudah terpakai seluruhnya:

- 2 kas: Harian (utama) dan Usaha (tambahan).
- 2 sumber dana: Tunai (utama) dan BCA (tambahan).

Panel kuota berubah merah, tombol tambah disembunyikan, dan validasi server tetap menolak penambahan.

---

## Skenario B: Banyak Kas / Sistem Amplop (akun Premium)

Pembagian kas per pos membutuhkan lebih dari 2 kas, sehingga hanya bisa untuk akun Premium aktif (termasuk masa trial). Rina memakai 5 kas dan 3 sumber dana.

### Tahap 1: Gaji masuk ke kas utama
Rina membuat kas **Utama** serta sumber dana **BCA**, **Tunai**, dan **GoPay** (ketiganya mulai dari 0). Pemasukan gaji Rp 5.000.000 dicatat ke kas Utama lewat BCA.

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Utama | 5.000.000 | BCA | 5.000.000 |
| | | Tunai | 0 |
| | | GoPay | 0 |

### Tahap 2: Membuat kas per pos
Rina membuat kas **Belanja Dapur**, **Jajan**, dan **Transport**, serta satu **Kas Tabungan** bernama **Dana Liburan** dengan target Rp 3.000.000 dan tenggat. Opsi **Pakai semua kategori saya di kas ini** dapat dicentang agar kategori yang sudah ada langsung terhubung.

### Tahap 3: Membagi uang dengan transfer antar-kas

| Transfer | Nominal |
|---|---|
| Utama → Belanja Dapur | 1.500.000 |
| Utama → Jajan | 400.000 |
| Utama → Transport | 600.000 |
| Utama → Dana Liburan | 500.000 |

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Utama | 2.000.000 | BCA | 5.000.000 |
| Belanja Dapur | 1.500.000 | Tunai | 0 |
| Jajan | 400.000 | GoPay | 0 |
| Transport | 600.000 | | |
| Dana Liburan | 500.000 | | |

### Tahap 4: Menyiapkan uang sesuai kebutuhan
Pemindahan saldo antar-sumber dana: BCA ke Tunai Rp 500.000 (pasar dan bensin), BCA ke GoPay Rp 300.000 (jajan dan ojol). Saldo kas tidak berubah.

| Sumber Dana | Saldo |
|---|---|
| BCA | 4.200.000 |
| Tunai | 500.000 |
| GoPay | 300.000 |

### Tahap 5: Pengeluaran sehari-hari

| Transaksi | Kas | Sumber Dana | Nominal |
|---|---|---|---|
| Belanja pasar | Belanja Dapur | Tunai | 250.000 |
| Kopi | Jajan | GoPay | 35.000 |
| Bensin | Transport | Tunai | 100.000 |
| Ojek online | Transport | GoPay | 25.000 |

### Hasil akhir

| Kas | Saldo | Sumber Dana | Saldo |
|---|---|---|---|
| Utama | 2.000.000 | BCA | 4.200.000 |
| Belanja Dapur | 1.250.000 | Tunai | 150.000 |
| Jajan | 365.000 | GoPay | 240.000 |
| Transport | 475.000 | | |
| Dana Liburan | 500.000 | | |
| **Total** | **4.590.000** | **Total** | **4.590.000** |

Dana Liburan: Rp 500.000 dari target Rp 3.000.000 (sekitar 16,7%), dengan status tenggat (Berjalan, Mendekati, atau Terlewat) dan estimasi tabungan per bulan.

### Catatan untuk tutorial
- **Satu kas atau banyak kas:** banyak kas cocok bagi yang ingin melihat sisa uang per pos secara langsung. Jika cukup melihat total per pos, satu kas dengan kategori sudah memadai.
- **Total saldo kas:** Kas Tabungan dapat dikeluarkan dari akumulasi Total Saldo Kas lewat **Pengaturan Tampilan** di halaman Kas.
- **Setelah Premium atau trial berakhir:** kas dan sumber dana ke-3 dan seterusnya tetap terlihat tetapi terkunci, tidak bisa dipilih untuk transaksi baru.
- **Kas Tabungan dan Kolaborator Kas** hanya tersedia untuk Premium.

### Alternatif untuk akun Reguler (maksimal 2 kas)
Pos-pos dijadikan **kategori**, bukan kas:

- **Kas Rumah Tangga** dengan kategori Belanja Dapur, Jajan, dan Transport.
- **Kas Pribadi** untuk pengeluaran di luar kebutuhan rumah.

Laporan menampilkan ringkasan per kategori beserta persentasenya, tetapi tanpa saldo terpisah per pos.

---

## Skenario C: Kas Bersama (Kas Keluarga)

Rina adalah pemilik kas dan harus **Premium aktif**. Budi cukup akun Reguler yang sudah terdaftar dan terverifikasi emailnya.

### Tahap 1: Mengundang kolaborator
Rina membuat kas **Keluarga**, lalu membuka **Pengaturan → Kolaborator Kas**.

| Kolaborator | Cara | Hak akses |
|---|---|---|
| Budi | Email Budi (harus sudah terdaftar dan terverifikasi) | Editor |
| Ibu | Email dikosongkan, terbentuk link publik | Viewer (otomatis) |

Status email terdaftar tampil hijau, belum terdaftar tampil merah dan ditolak. Pemilik tidak bisa mengundang dirinya sendiri atau akun admin. Tanggal **Mulai** dan **Berlaku hingga** dapat diatur, dan akses dapat dicabut kapan saja.

### Tahap 2: Siapa memakai apa
- **Kas** dan **kategori** dimiliki bersama; Editor boleh mengelola kategori kas tersebut.
- **Sumber dana** tetap milik masing-masing; Budi mencatat memakai sumber dana miliknya sendiri (misalnya Mandiri), bukan BCA milik Rina.
- Kas bersama **tidak dihitung ke kuota kas Budi**.

### Tahap 3: Transaksi bersama

| Transaksi | Dicatat oleh | Kas | Sumber Dana | Nominal |
|---|---|---|---|---|
| Pemasukan: setoran bulanan | Rina | Keluarga | BCA (Rina) | 2.000.000 |
| Pemasukan: setoran bulanan | Budi | Keluarga | Mandiri (Budi) | 1.500.000 |
| Pengeluaran: listrik | Rina | Keluarga | BCA (Rina) | 300.000 |
| Pengeluaran: belanja bulanan | Budi | Keluarga | Mandiri (Budi) | 450.000 |

Saldo kas Keluarga: 2.000.000 + 1.500.000 - 300.000 - 450.000 = **Rp 2.750.000**.

| Pemilik | Sumber Dana | Dampak |
|---|---|---|
| Rina | BCA | +1.700.000 |
| Budi | Mandiri | +1.050.000 |

Total dampak sumber dana (1.700.000 + 1.050.000) sama dengan saldo kas Keluarga.

### Tahap 4: Hak akses

| | Rina (Pemilik) | Budi (Editor) | Ibu (Viewer via link) |
|---|---|---|---|
| Lihat saldo dan transaksi kas | Ya | Ya | Ya, tanpa login |
| Tambah transaksi | Ya | Ya | Tidak |
| Ubah/hapus transaksi | Semua | Hanya buatannya sendiri | Tidak |
| Kelola kategori kas | Ya | Ya | Tidak |
| Transfer kas, pindah sumber dana, import | Ya | Tidak (versi awal) | Tidak |
| Nama sumber dana anggota lain | Disamarkan | Disamarkan | Tidak ditampilkan |

Halaman link publik menampilkan saldo kas, transaksi berhalaman, filter bulan, pencarian, serta total pemasukan dan pengeluaran bulan itu, tanpa nama sumber dana dan identitas pengguna. Mencabut akses langsung menonaktifkan link. Nama pencatat tampil di bawah tanggal pada daftar transaksi.

### Catatan untuk tutorial
- Syarat Premium hanya untuk pemilik. Kolaborasi yang sudah ada tetap dapat dikelola dan dicabut walau Premium pemilik berakhir, tetapi kolaborator baru tidak bisa ditambah.
- Transaksi pada kas terbatas tidak dapat diubah atau dihapus.
- **Cocokkan Saldo** dilakukan masing-masing pemilik sumber dana, karena sumber dana anggota lain disamarkan.
- Link publik cocok untuk anggota yang hanya perlu memantau; kolaborator dengan email bersifat privat.

---

## Pengingat Penulisan Tutorial

- Gunakan istilah **Sumber Dana**, **Cocokkan Saldo**, dan **Penyesuaian Saldo**; jangan memakai "dompet" atau "audit" pada teks yang tampil ke pengguna.
- Urutan yang disarankan: Skenario A (pemula), Skenario B dan C (lanjutan, memerlukan Premium).
- Fitur khusus admin tidak dimasukkan ke tutorial publik.
- Setelah dipindahkan ke `tutorial.json`, sesuaikan test tutorial yang terdampak dan keterangan terkait di `FITUR_APLIKASI.md`.
