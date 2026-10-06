# Rencana Perubahan: Dompet → Sumber Dana + Jenis (APKu)

Dokumen ini merangkum rencana mengubah entitas **Dompet** menjadi **Sumber Dana** yang memiliki **jenis** tetap (Tunai, Rekening, E-wallet, Lainnya). "Dompet" tidak lagi menjadi nama entitas, melainkan contoh nama untuk sumber dana berjenis Tunai.

- Tanggal: 6 Oktober 2026
- Status aplikasi: belum production, belum ada data nyata
- Prasyarat: rename Aktivitas → Kategori sudah selesai
- Acuan: `FITUR_APLIKASI.md` versi terbaru dan laporan coverage

---

## 1. Tujuan

Memisahkan istilah entitas dari contoh isinya. Sebelumnya "Dompet" dipakai sebagai nama entitas sekaligus contoh ("kas tunai atau rekening bank"). Dengan jenis, perilaku khusus per jenis (misalnya penghitung uang hanya untuk Tunai) dapat diatur secara eksplisit.

## 2. Keputusan yang sudah ditetapkan

| # | Topik | Keputusan |
|---|---|---|
| 1 | Nama entitas | **Sumber Dana** |
| 2 | Jenis data | Tunai, Rekening, E-wallet, Lainnya (daftar tetap, tidak dapat ditambah pengguna) |
| 3 | Contoh nama | "Dompet" adalah contoh nama sumber dana berjenis Tunai; "BCA" contoh Rekening; "GoPay" contoh E-wallet |
| 4 | Penghitung uang di audit | Hanya untuk jenis Tunai |
| 5 | Kuota | Tetap per entitas, tidak bergantung pada jenis |
| 6 | Transfer antar-sumber dana | Boleh lintas jenis |
| 7 | Laporan dan filter | Cukup filter per sumber dana; tanpa ringkasan atau filter per jenis |
| 8 | Data | Belum ada data production |

### Keputusan rekomendasi (disetujui sebagai arah, dapat disesuaikan)

- Satu sumber dana punya tepat satu jenis, dan jenis wajib diisi.
- Jenis boleh diubah setelah dibuat. Riwayat audit lama tidak diubah secara retroaktif, dan rincian pecahan yang sudah tersimpan tetap utuh dan tetap tampil.
- Tidak menambah data bank atau nomor rekening pada tahap ini.
- Informasi jenis ikut disamarkan untuk anggota lain, sama seperti nama sumber dana.

## 3. Desain

### 3.1 Enum jenis

```php
enum JenisSumberDana: string
{
    case Tunai = 'tunai';
    case Rekening = 'rekening';
    case EWallet = 'e_wallet';
    case Lainnya = 'lainnya';

    public function mendukungHitungUang(): bool
    {
        return $this === self::Tunai;
    }
}
```

Disimpan sebagai string biasa (bukan enum database) agar penambahan jenis cukup lewat kode. Metode `mendukungHitungUang()` menjadi satu-satunya sumber logika "hanya Tunai" untuk form, validasi, dan service.

### 3.2 Skema (sketsa, sesuaikan dengan nama sebenarnya)

```php
Schema::rename('dompet', 'sumber_dana');
// Ganti nama kolom FK di semua tabel yang memakai dompet_id
// (transaksi, audit, akses/kolaborasi, dan lainnya)

Schema::table('sumber_dana', function (Blueprint $table) {
    $table->string('jenis', 20)->default('tunai')->after('nama')->index();
});
```

### 3.3 Perilaku per jenis

| Jenis | Form audit saldo |
|---|---|
| Tunai | Penghitung uang kertas dan logam, termasuk toggle **Uang Logam**; saldo riil juga dapat diisi manual |
| Rekening | Saldo riil manual |
| E-wallet | Saldo riil manual |
| Lainnya | Saldo riil manual |

Validasi di sisi server wajib menolak atau mengabaikan data pecahan untuk jenis selain Tunai, tidak hanya menyembunyikannya di UI.

**Aturan untuk riwayat audit** (dikonfirmasi: audit saat ini menyimpan rincian pecahan per dompet):

- Aturan "hanya Tunai" berlaku untuk **audit baru** dan mengikuti jenis sumber dana saat audit dibuat.
- Tampilan riwayat audit menentukan penampilan rincian pecahan dari **data yang tersimpan**, bukan dari jenis sumber dana saat ini. Jika sumber dana diubah dari Tunai ke Rekening, audit lamanya tetap menampilkan rincian pecahan.
- Mengubah jenis tidak boleh menghapus, mengosongkan, atau menghitung ulang data audit yang sudah ada.
- Opsional: simpan snapshot jenis pada baris detail audit agar riwayat tetap jelas jika jenis berubah.

### 3.4 Pemetaan istilah

| Sebelum | Sesudah |
|---|---|
| Dompet | Sumber dana |
| Dompet utama / default | Sumber dana utama / default |
| Transfer dompet / pemindahan saldo antar-dompet | Transfer antar-sumber dana |
| Kas & Dompet (tab dashboard) | Kas & Sumber Dana |
| Jumlah Dompet (tabel langganan) | Jumlah Sumber Dana |
| Dompet tidak aktif (penanda) | Sumber dana tidak aktif |
| Halaman Dompet dan menu Setting Dompet | Halaman dan menu Sumber Dana |

**Jangan diubah:** "dompet digital" pada metode pembayaran di administrasi langganan (bank, dompet digital, QR). Itu konsep berbeda dan tidak terkait entitas ini.

## 4. Area yang terdampak

| Area | Perubahan |
|---|---|
| Onboarding | "Dompet utama" menjadi sumber dana utama, default jenis Tunai dan nama "Dompet" |
| Form transaksi dan ubah transaksi | Label, field jenis pada form sumber dana, pembuatan cepat bagi Premium |
| Transfer | Label dan alur transfer antar-sumber dana; lintas jenis diperbolehkan |
| Import | Kolom referensi pada template XLSX, nilai kosong memakai sumber dana default |
| Pencarian global dan filter | Label dan filter sumber dana |
| Laporan | Filter sumber dana (tanpa filter jenis) |
| Dashboard | Tab "Kas & Sumber Dana", kartu saldo, penanda status |
| Audit saldo | Form, riwayat, tombol "Audit saldo" dan "Riwayat audit", penghitung hanya untuk Tunai |
| Kolaborasi dan halaman publik | Editor memakai sumber dana miliknya sendiri; nama dan jenis disamarkan untuk anggota lain; halaman publik tetap tidak menampilkannya |
| Kuota dan langganan | Pesan kuota, panel slot, tabel perbandingan Reguler vs Premium |
| Cache | Daftar dengan cache tiga hari: ganti kunci dan pastikan invalidasi |
| Setting dan route | Menu Setting, URL `/admin/pengaturan/...`, breadcrumb |
| Policy dan service | Rename kelas, relasi, dan hak akses |
| Konten dan data | `tutorial.json`, seeder, factory, `FITUR_APLIKASI.md` |

## 5. Pengaman: kondisi test

Acuan dari laporan coverage terakhir (line 86,85%):

| Area | Lines | Catatan |
|---|---|---|
| Filament | 91,6% | Kuat |
| Models | 89,0% | Cukup kuat |
| Observers | 95,2% | Kuat |
| Services | 77,7% | Kemungkinan memuat logika audit, transfer, laporan |
| Policies | 71,7% | Hak Editor dan penyamaran data anggota lain |
| Jobs | 65,5% | Import antrean |

### Test yang perlu ditambah

- Audit jenis Tunai menghitung pecahan seperti sekarang.
- Audit jenis non-Tunai menolak atau mengabaikan data pecahan.
- Transfer antar-sumber dana lintas jenis berhasil dan saldo konsisten.
- Kuota Reguler tidak bergantung pada jenis.
- Jenis ikut disamarkan untuk anggota lain.
- Import dengan kolom sumber dana kosong tetap memakai default.
- Mengubah jenis dari Tunai ke jenis lain tidak menghapus atau mengubah rincian pecahan pada audit lama.
- Riwayat audit lama tetap menampilkan rincian pecahan setelah jenis berubah.
- Audit baru pada sumber dana yang sudah berganti jenis mengikuti jenis terbaru.

### Temuan yang sudah dikonfirmasi

Riwayat audit menyimpan rincian pecahan per dompet. Karena itu, perubahan jenis tidak boleh memengaruhi data audit lama (lihat aturan riwayat audit di bagian 3.3), dan rename tabel serta kolom pada Fase B harus menjaga data pecahan tetap utuh.

## 6. Rencana kerja bertahap

Pecah menjadi commit atau PR terpisah agar perubahan struktur tidak bercampur dengan perubahan perilaku.

### Fase A: Baseline

```bash
git switch -c refactor/sumber-dana
php artisan test
grep -rniE "dompet|wallet" app database resources routes tests -l | sort
grep -rniE "dompet|wallet" app database resources routes tests | wc -l
```

Hasil: jumlah test lulus, angka coverage awal, dan daftar file yang menyebut dompet.

### Fase B: Rename murni (tanpa perubahan perilaku)

- Migration rename tabel dan semua kolom FK, model, relasi, resource Filament, policy, route, label UI, tutorial, dan dokumentasi.
- Kecualikan "dompet digital" pada metode pembayaran admin.
- Perbarui kunci cache dan penanda status.
- Seluruh test harus tetap hijau.

### Fase C: Tambah jenis

- Enum `JenisSumberDana`, cast pada model, kolom `jenis`.
- Field jenis wajib pada form tambah dan ubah, termasuk pembuatan cepat dari form transaksi.
- Onboarding membuat sumber dana utama berjenis Tunai dengan nama default "Dompet".
- Seeder dan factory diperbarui.
- Status selesai: sudah diimplementasikan dalam commit `ecc9d3a`.

### Fase D: Perilaku per jenis

- Penghitung uang pada form audit hanya tampil untuk sumber dana berjenis Tunai.
- Server menolak atau mengabaikan pecahan untuk jenis non-Tunai, bukan hanya menyembunyikannya di UI.
- Audit baru memakai jenis sumber dana saat dibuat, sedangkan riwayat lama tetap membaca data pecahan yang tersimpan.
- Validasi tambahan untuk penyamaran jenis, transfer lintas jenis, dan audit historis.pil untuk Tunai; validasi di server.- Penyamaran jenis untuk anggota lain.
- Tampilan riwayat audit berbasis data tersimpan, bukan jenis saat ini; opsional tambahkan snapshot jenis pada detail audit.
- Opsional: ikon atau warna per jenis pada kartu daftar.

### Fase E: Penutup

- Perbarui `FITUR_APLIKASI.md` dan `tutorial.json`.
- Jalankan seluruh test dan bandingkan coverage dengan baseline Fase A.
- Telusuri sisa kata "dompet" dengan grep dan pastikan hanya "dompet digital" (admin) dan contoh nama "Dompet" yang tersisa.

## 7. Risiko dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Penggantian kata "dompet" secara buta mengubah konsep lain | Rename murni terpisah, kecualikan "dompet digital", tinjau diff per file |
| Penghitung uang masih bisa dikirim untuk non-Tunai lewat request langsung | Validasi di server, bukan hanya di UI |
| Rincian pecahan audit lama hilang atau tersembunyi saat jenis diubah | Tampilan riwayat berbasis data tersimpan; jenis tidak menyentuh data audit; uji khusus untuk skenario ini |
| Kuota membingungkan karena pengguna ingin banyak jenis | Pesan kuota menyebut jumlah sumber dana, bukan jenis; tinjau setelah ada umpan balik |
| Informasi jenis bocor ke anggota lain | Perluas aturan penyamaran dan tambahkan test |
| Cache daftar lama masih memakai kunci "dompet" | Ganti kunci dan uji invalidasi |

## 8. Definisi selesai

- Seluruh test lulus dan coverage tidak turun dari baseline.
- Tidak ada lagi istilah "dompet" sebagai nama entitas di UI, tutorial, dan dokumentasi.
- Setiap sumber dana memiliki jenis, dan penghitung uang hanya muncul serta berlaku untuk Tunai.
- Transfer lintas jenis berfungsi, dan kuota tetap per entitas.
- Jenis dan nama sumber dana milik anggota lain tetap disamarkan.
- Riwayat audit lama tetap utuh dan menampilkan rincian pecahan walaupun jenis sumber dana telah berubah.
