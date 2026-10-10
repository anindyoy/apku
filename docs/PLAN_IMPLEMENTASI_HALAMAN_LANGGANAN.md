# Plan Implementasi: Halaman Tambah Langganan APKu

Mengubah prototipe HTML halaman langganan (grid kartu paket, info hemat per bulan, modal konfirmasi) menjadi fitur nyata di aplikasi Laravel + Filament + Livewire.

- Prototipe acuan: docs/Langganan Premium - APKu.html
- Sumber aturan bisnis: `FITUR_APLIKASI.md`, bagian 12 dan 13 (Langganan premium)

## 1. Tujuan dan ruang lingkup

**Tujuan:** user memilih paket dari grid kartu, melihat penghematan per bulan tiap paket, lalu membuat order lewat modal konfirmasi (metode pembayaran, voucher, ringkasan harga).

**Termasuk:**
- Grid kartu paket aktif dengan harga, harga per bulan, harga coret, dan hemat per bulan.
- Satu paket dengan hemat terbesar disorot otomatis ("Paling hemat").
- Modal konfirmasi: metode pembayaran, kode voucher, ringkasan total.
- Pembuatan order memakai alur yang sudah ada (snapshot order, invoice terbuka di tab baru).
- Mode gelap, responsif, aksesibel. Aksen warna teal sesuai brand.

**Tidak termasuk (rencana terpisah):** fitur coba gratis Premium 30 hari, perubahan alur verifikasi pembayaran oleh admin, perubahan struktur voucher.

## 2. Keputusan desain yang sudah diambil

| Topik | Keputusan |
|---|---|
| Harga acuan per bulan | Otomatis dari paket aktif berdurasi terpendek |
| Hemat per bulan | acuan per bulan dikurangi harga per bulan paket itu |
| Persen hemat | `floor((1 - harga / harga_coret) * 100)` |
| Harga coret | acuan per bulan dikali jumlah bulan paket |
| Paket acuan | Tanpa badge hemat, teks "Harga acuan per bulan" |
| Sorotan | Paket dengan `hemat_bulan` terbesar (hanya jika lebih dari 0) |
| Setelah klik Beli | Modal konfirmasi (metode, voucher, ringkasan) |
| Gaya | Kartu putih minimalis, aksen teal (`teal-700`), label sorotan kuning (`amber-300`) |
| Urutan kartu | Durasi terpanjang ke terpendek |

## 3. Audit awal di repo (sebelum menulis kode)

Cek dan catat nama aktual berikut, karena plan ini memakai nama sementara:

- [ ] Resource/halaman Langganan sisi user di Filament (tempat form order saat ini)
- [ ] Model dan tabel: `PaketLangganan` (`paket_langganans`), `MetodePembayaran`, `Voucher`, `VoucherCode`, `Langganan`
- [ ] Kode yang membuat order saat ini (action/service) beserta validasi voucher dan pengisian snapshot (`label_paket`, `harga`, `durasi_hari`, `kode_voucher`, `persentase_diskon`, `nominal_diskon`, `total_pembayaran`)
- [ ] Route dan cara invoice dibuka otomatis di tab baru
- [ ] Versi Filament dan Tailwind yang dipakai, serta apakah panel sudah memakai custom theme
- [ ] Tabel perbandingan Reguler vs Premium dan kartu riwayat langganan yang harus tetap utuh

Prinsip: **pakai ulang logika order yang ada**, jangan membuat jalur kedua.

## 4. Arsitektur

### 4.1 Service perhitungan hemat (PHP murni, mudah diuji)

`App\Services\Langganan\HitungHematPaket`: menerima koleksi paket aktif, mengembalikan data siap tampil per paket. Sketsa:

```php
final class HitungHematPaket
{
    /** @param Collection<int, PaketLangganan> $paket paket aktif */
    public function handle(Collection $paket): Collection
    {
        $items = $paket->map(fn ($p) => [
            'id' => $p->id,
            'label' => $p->label,
            'harga' => $p->harga,
            'hari' => $p->durasi_hari,
            'bulan' => max(1, (int) round($p->durasi_hari / 30)),
        ]);

        $acuan = $items->sortBy('hari')->first();
        if (! $acuan) {
            return collect();
        }
        $acuanPerBulan = $acuan['harga'] / $acuan['bulan'];

        $hasil = $items->map(function ($i) use ($acuanPerBulan, $acuan) {
            $perBulan = $i['harga'] / $i['bulan'];
            $coret = $acuanPerBulan * $i['bulan'];

            return $i + [
                'per_bulan' => $perBulan,
                'harga_coret' => $coret,
                'hemat_bulan' => max(0, $acuanPerBulan - $perBulan),
                'hemat_total' => max(0, $coret - $i['harga']),
                'persen' => $coret > 0 ? (int) floor((1 - $i['harga'] / $coret) * 100) : 0,
                'is_acuan' => $i['id'] === $acuan['id'],
            ];
        });

        $terbaik = $hasil->sortByDesc('hemat_bulan')->first();

        return $hasil
            ->map(fn ($i) => $i + [
                'is_terbaik' => $terbaik && $terbaik['hemat_bulan'] > 0 && $i['id'] === $terbaik['id'],
            ])
            ->sortByDesc('hari')
            ->values();
    }
}
```

Perhitungan dilakukan di server, hasilnya hanya ditampilkan di view.

### 4.2 Komponen Livewire / halaman Filament

Komponen `PilihPaketLangganan` (atau halaman kustom Filament, sesuai hasil audit):

- State: `paketId`, `metodeId`, `kodeVoucher`, `voucherDiterapkan` (id kode, persen), `modalTerbuka`.
- Aksi: `pilihPaket($id)`, `terapkanVoucher()`, `buatOrder()`.
- `buatOrder()` memanggil action/service order yang sudah ada. **Total dihitung ulang di server** dari harga paket dan voucher yang divalidasi saat itu, tidak memakai angka dari browser.
- Setelah sukses: notifikasi, lalu buka invoice di tab baru (perilaku saat ini, termasuk tombol "Lihat invoice" jika tab diblokir peramban).

### 4.3 View Blade

- Satu view untuk grid, satu partial untuk kartu, satu partial untuk modal.
- Modal digerakkan Alpine (sudah dibawa Filament), tidak perlu memuat JS Flowbite. Class Flowbite/Tailwind dari prototipe dapat disalin langsung.
- Kartu mengikuti struktur prototipe: nama paket, harga, harga per bulan + coret, teks hemat, tombol "Beli paket".

### 4.4 Tema Tailwind (poin penting)

Panel Filament memakai CSS terkompilasi sendiri, sehingga class Tailwind di view kustom **tidak otomatis tersedia**.

- Buat/gunakan custom theme Filament (`php artisan make:filament-theme`) dan tambahkan path view komponen ini ke `content` di konfigurasi Tailwind tema.
- Dark mode Filament memakai class `.dark`, sesuai varian `dark:` pada prototipe.
- Samakan warna aksen: atur `primary` panel ke `Color::Teal` agar komponen bawaan Filament ikut serasi.
- Jalankan build asset (`npm run build`) dan uji di mode terang dan gelap.

## 5. Langkah kerja

**Tahap 1: Fondasi**
- [ ] Audit repo (bagian 3) dan catat nama class/route nyata.
- [ ] Tulis `HitungHematPaket` dan tes unit (data uji: 3 bln Rp63.000, 6 bln Rp126.000, 9 bln Rp169.000, 1 thn Rp199.000).

**Tahap 2: Tampilan**
- [ ] Siapkan theme Filament dan konfigurasi Tailwind.
- [ ] Buat view grid dan partial kartu dari prototipe.
- [ ] Hubungkan data dari service ke view, termasuk sorotan "Paling hemat" dan state kosong.

**Tahap 3: Interaksi**
- [ ] Modal konfirmasi (Alpine) dengan pilihan metode pembayaran aktif.
- [ ] Voucher: validasi server, pesan galat jelas, ringkasan harga ikut berubah.
- [ ] `buatOrder()` memanggil alur order yang ada, lalu buka invoice.

**Tahap 4: Kualitas**
- [ ] Mode gelap, responsif (1/2/4 kolom), fokus keyboard, `Esc` menutup modal, fokus kembali ke tombol Beli.
- [ ] Tombol dinonaktifkan saat proses (`wire:loading`) untuk mencegah order ganda.
- [ ] Tes fitur (bagian 7).

**Tahap 5: Dokumentasi dan rilis**
- [ ] Perbarui `FITUR_APLIKASI.md` (bagian 13 frontend langganan; skema database hanya jika ada kolom baru).
- [ ] Perbarui topik langganan di `resources/content/tutorial.json` jika tampilannya berubah.
- [ ] QA manual di desktop dan ponsel, terang dan gelap, lalu rilis.

## 6. Kasus tepi dan keputusan terbuka

| Kasus | Penanganan yang diusulkan |
|---|---|
| Tidak ada paket aktif | State kosong: "Belum ada paket yang tersedia." |
| Hanya satu paket aktif | Tanpa hemat dan tanpa sorotan |
| Tidak ada paket yang lebih murah per bulan dari acuan | Tidak ada kartu yang disorot |
| Durasi bukan kelipatan 30 hari (mis. 365 hari) | Jumlah bulan = `round(hari / 30)`, sehingga 365 hari menjadi 12 bulan. **Putuskan:** cukup dibulatkan, atau tambah kolom `jumlah_bulan` eksplisit di `paket_langganans` agar admin menentukan sendiri |
| Voucher kedaluwarsa, tidak ditemukan, atau non-berulang yang sudah dipakai | Pesan galat spesifik; total tidak berubah |
| Voucher 100% (total Rp0) | **Putuskan:** diizinkan atau ditolak, sesuaikan dengan perilaku order saat ini |
| Klik Buat order dua kali | Tombol nonaktif selama proses; order dibuat sekali |
| Harga paket diubah admin saat modal terbuka | Total dihitung ulang di server saat order dibuat |
| Pembulatan rupiah | Tampilan dibulatkan ke rupiah; nilai tersimpan tetap integer seperti sekarang |
| Metode pembayaran aktif kosong | Tombol Buat order nonaktif dengan pesan arahan |

## 7. Rencana pengujian (Pest)

**Unit: `HitungHematPaket`**
- Data contoh menghasilkan: 1 tahun hemat Rp4.417/bulan (21%), 9 bulan hemat Rp2.222/bulan (10%), 3 bulan sebagai acuan.
- Paket 6 bulan tanpa hemat; tidak disorot.
- Hanya satu paket, daftar kosong, dan harga sama semua.
- Hanya satu paket yang `is_terbaik`; urutan paket durasi terpanjang dulu.

**Fitur / Livewire**
- Grid hanya menampilkan paket aktif.
- Memilih paket membuka modal dengan ringkasan benar.
- Voucher valid, tidak ditemukan, kedaluwarsa, dan non-berulang yang sudah dipakai.
- Order tersimpan dengan snapshot yang benar dan total dihitung server (angka dari klien yang dimanipulasi diabaikan).
- Hanya user yang berhak yang dapat membuat order; admin tidak memakai halaman ini.
- Order ganda tidak terbentuk saat aksi dipicu dua kali.

## 8. Kriteria selesai

- Tampilan setara prototipe di terang dan gelap, pada lebar 360px sampai desktop.
- Angka hemat tepat dan dihitung server dari paket aktif.
- Order yang dibuat identik dengan hasil alur lama (snapshot, status, invoice).
- Semua tes lulus; dokumentasi `FITUR_APLIKASI.md` dan tutorial sudah diperbarui.

## 9. Risiko

- **CSS tidak muncul di panel Filament** karena theme belum memuat path view baru. Atasi di Tahap 2.
- **Duplikasi logika order** jika modal membuat jalur baru. Atasi dengan memakai ulang action yang ada.
- **Angka hemat membingungkan** bila acuan berubah saat admin menambah paket yang lebih pendek. Akibatnya semua hemat ikut bergeser; beri tahu admin atau pertimbangkan acuan manual di Setting.
