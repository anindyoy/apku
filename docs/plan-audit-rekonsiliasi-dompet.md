# Rencana Audit dan Rekonsiliasi Dompet

## Status

Ditunda untuk sesi tugas terpisah. Implementasi fitur utama dompet tidak bergantung pada penyelesaian rencana ini.

Class `AuditIntegritasDompet` tidak boleh dibuat kembali sebelum desain audit pada dokumen ini ditinjau dan disepakati.

## Tujuan

- Mendeteksi data keuangan yang tidak konsisten tanpa mengubah data secara otomatis.
  - Contoh: saldo tersimpan pada dompet Cash adalah Rp500.000, sedangkan hasil penjumlahan seluruh transaksi dompet tersebut hanya Rp450.000. Command melaporkan selisih Rp50.000 beserta ID pengguna dan dompet, tetapi tidak mengubah saldo saat dijalankan dalam mode bawaan.
- Menyediakan mode perbaikan yang eksplisit, aman, idempoten, dan dapat diaudit.
  - Contoh: pengguna mempunyai dua dompet berstatus default. Operator menjalankan command dengan opsi perbaikan yang eksplisit; sistem mempertahankan dompet default yang dipilih berdasarkan aturan deterministik, menonaktifkan status default lainnya, dan mencatat hasil perbaikan. Ketika command yang sama dijalankan kembali, tidak ada perubahan tambahan.
- Mendukung pemeriksaan sebelum dan sesudah deployment atau backfill data.
  - Contoh: sebelum backfill, command mencatat 120 transaksi yang belum memiliki `dompet_id`. Setelah backfill dijalankan, command diperiksa kembali dan harus melaporkan nol transaksi tanpa dompet, pasangan transfer tetap lengkap, serta tidak ada perubahan saldo yang tidak diharapkan.

## Pemeriksaan minimum

- Pengguna tanpa buku kas atau dompet.
- Pengguna tanpa record default atau dengan lebih dari satu buku kas/dompet default.
- Transaksi tanpa buku kas atau dompet.
- Kepemilikan silang antara pengguna, buku kas, dompet, kategori, dan transaksi.
- Pasangan transfer yang hilang atau berjumlah selain dua.
- Pasangan transfer dengan pengguna, nominal, tipe transfer, buku kas, dompet, atau `transfer_code` yang tidak konsisten.
- Saldo buku kas yang berbeda dari agregasi histori transaksi.
- Saldo dompet yang berbeda dari agregasi histori transaksi.
- Referensi transaksi ke dompet yang dihapus secara lunak tetap valid.

## Perilaku command

- Mode bawaan hanya melakukan pemeriksaan dan tidak menulis perubahan.
- Menghasilkan ringkasan jumlah pelanggaran per jenis serta exit code non-zero jika ditemukan masalah.
- Tidak menampilkan data sensitif lebih dari yang diperlukan untuk identifikasi record.
- Mode perbaikan hanya berjalan melalui opsi eksplisit.
- Setiap perbaikan dijalankan dalam transaksi database dan aman dijalankan ulang.
- Perbaikan tidak boleh menghapus atau memindahkan histori transaksi secara diam-diam.
- Selisih saldo harus dilaporkan sebelum diperbaiki dan hasil sesudah perbaikan harus diverifikasi ulang.

## Kapan audit dijalankan

Audit perlu dijalankan pada kondisi berikut:

- Sebelum deployment fitur dompet ke produksi untuk memperoleh kondisi awal data.
- Setelah migration dan backfill untuk memastikan seluruh transaksi memiliki buku kas dan dompet yang valid.
- Setelah deployment untuk memastikan transaksi baru tidak menghasilkan selisih saldo atau pasangan transfer yang rusak.
- Sebelum dan sesudah perubahan besar pada transaksi, saldo, transfer, atau penghapusan dompet.
- Setelah kegagalan server, timeout, atau proses database yang terputus ketika transaksi berlangsung.
- Ketika pengguna melaporkan saldo buku kas atau dompet yang tidak sesuai dengan histori transaksi.
- Secara berkala, misalnya setiap malam atau setiap minggu, menggunakan mode pemeriksaan saja.
- Sebelum menjalankan mode perbaikan dan sekali lagi setelahnya untuk memverifikasi hasil.

Urutan yang disarankan saat deployment:

1. Jalankan audit dalam mode pemeriksaan dan simpan hasilnya.
2. Jalankan migration atau backfill.
3. Jalankan audit kembali untuk memeriksa hasil migrasi data.
4. Deploy kode aplikasi.
5. Jalankan audit setelah beberapa transaksi produksi tercatat.
6. Aktifkan audit berkala dalam mode pemeriksaan.

Mode perbaikan tidak dijalankan secara otomatis. Mode tersebut hanya digunakan setelah hasil audit ditinjau, backup tersedia, dan jenis perbaikannya telah disetujui.

## Rencana pengujian

- Pemeriksaan pada data valid menghasilkan exit code sukses.
- Pengguna tanpa buku kas/dompet terdeteksi.
- Default yang hilang atau ganda terdeteksi.
- Kepemilikan silang terdeteksi.
- Pasangan transfer rusak terdeteksi.
- Selisih saldo buku kas dan dompet terdeteksi.
- Mode pemeriksaan tidak mengubah data.
- Mode perbaikan memperbaiki kasus yang didukung dan bersifat idempoten.
- Kegagalan perbaikan membatalkan seluruh perubahan terkait.

Setiap test lokal wajib menggunakan `--filter` dan, untuk Pest v5, dikombinasikan dengan `--tia` sesuai `AGENTS.md`.

## Keputusan yang diperlukan sebelum coding

- Jenis pelanggaran apa saja yang boleh diperbaiki otomatis.
- Apakah selisih saldo diperbaiki dengan mengubah cache saldo atau membuat transaksi penyesuaian.
- Format keluaran command yang dibutuhkan untuk deployment dan penyimpanan bukti audit.
- Apakah pemeriksaan dijalankan untuk satu pengguna, seluruh pengguna, atau keduanya.
