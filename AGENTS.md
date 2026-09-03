# Aturan Proyek

Aturan berikut berlaku untuk seluruh pekerjaan di folder proyek ini dan semua subfoldernya.

## Klarifikasi Prompt

- Jika prompt pengguna belum memuat konteks yang diperlukan untuk memberikan jawaban atau menjalankan tugas secara akurat, ajukan pertanyaan klarifikasi kepada pengguna sebelum melanjutkan. Pertanyaan harus spesifik pada informasi yang masih kurang dan relevan dengan tugas.

## Testing

- Setiap kode yang baru dibuat atau dimodifikasi harus diuji secara terprogram (programmatic test). Jalankan test dengan filter yang menyasar baris atau fungsi yang diubah; jangan menjalankan seluruh suite tanpa filter.
- Setiap perintah test lokal wajib menggunakan flag `--filter` yang secara spesifik menyasar test, fungsi, atau perilaku yang terdampak. Penyebutan satu atau beberapa path file test saja belum dianggap sebagai filter.
- Jangan menjalankan full test suite di lokal, misalnya `php artisan test` tanpa filter atau flag dan `npm test` tanpa filter. Full suite dijalankan di GitHub Actions atau CI, bukan di mesin lokal.
- Khusus proyek Laravel dengan Pest, kombinasikan `--filter` dengan flag `--tia` (Test Impact Analysis) saat menjalankan test di lokal jika versi Pest yang terpasang sudah v5 atau lebih baru. Jika Pest masih di bawah v5 dan belum mendukung `--tia`, kombinasikan `--filter` dengan `--parallel`. Periksa versi Pest yang terpasang, misalnya dengan `composer show pestphp/pest`, sebelum memilih flag.
- Hindari penggunaan helper assertSee pada testing, karena kalau eror sulit dicek masalahnya.

## Komentar Kode

- Setiap catatan atau komentar pada kode, baik satu baris maupun blok, harus ditulis dalam Bahasa Indonesia.

## Git

- Jangan melakukan `git commit` kecuali diminta secara eksplisit oleh pengguna. Perubahan boleh dibuat atau diedit di working tree, tetapi commit harus menunggu instruksi langsung.
