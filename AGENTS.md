# Aturan Proyek

Aturan berikut berlaku untuk seluruh pekerjaan di folder proyek ini dan semua subfoldernya.

## Testing

- Setiap kode yang baru dibuat atau dimodifikasi harus diuji secara terprogram (programmatic test). Jalankan test dengan filter yang menyasar baris atau fungsi yang diubah; jangan menjalankan seluruh suite tanpa filter.
- Jangan menjalankan full test suite di lokal, misalnya `php artisan test` tanpa filter atau flag dan `npm test` tanpa filter. Full suite dijalankan di GitHub Actions atau CI, bukan di mesin lokal.
- Khusus proyek Laravel dengan Pest, gunakan flag `--tia` (Test Impact Analysis) saat menjalankan test di lokal jika versi Pest yang terpasang sudah v5 atau lebih baru. Jika Pest masih di bawah v5 dan belum mendukung `--tia`, gunakan `--parallel`. Periksa versi Pest yang terpasang, misalnya dengan `composer show pestphp/pest`, sebelum memilih flag.

## Komentar Kode

- Setiap catatan atau komentar pada kode, baik satu baris maupun blok, harus ditulis dalam Bahasa Indonesia.

## Git

- Jangan melakukan `git commit` kecuali diminta secara eksplisit oleh pengguna. Perubahan boleh dibuat atau diedit di working tree, tetapi commit harus menunggu instruksi langsung.
