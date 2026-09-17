# Aturan Proyek

Aturan berikut berlaku untuk seluruh pekerjaan di folder proyek ini dan semua subfoldernya.

## Acuan Fitur Aplikasi

- Baca dan gunakan [`FITUR_APLIKASI.md`](FITUR_APLIKASI.md) sebagai acuan untuk memahami fitur, alur pengguna, hak akses, dan cakupan aplikasi yang tersedia.
- Jika suatu perubahan menambah, mengubah, menonaktifkan, atau menghapus fitur aplikasi, perbarui [`FITUR_APLIKASI.md`](FITUR_APLIKASI.md) agar tetap sesuai dengan implementasi terbaru.
- Jika terdapat perbedaan antara rangkuman fitur dan implementasi, source code serta test yang berlaku menjadi sumber kebenaran; sesuaikan rangkuman fitur dalam pekerjaan yang sama.

## Pemeliharaan Tutorial Pengguna

- Setiap penambahan, perubahan, penonaktifan, atau penghapusan fitur yang dapat diakses pengguna non-admin wajib disertai pembaruan [`resources/content/tutorial.json`](resources/content/tutorial.json) dalam pekerjaan yang sama, tanpa menunggu permintaan terpisah dari pengguna.
- Baca tutorial yang berkaitan sebelum mengubah fitur. Sesuaikan langkah penggunaan, nama menu atau tombol, contoh, batasan, dan catatan hak akses dengan implementasi terbaru, termasuk perbedaan akun Reguler, Premium, serta akses kas bersama.
- Tambahkan topik untuk fitur baru yang belum tercakup. Untuk fitur yang dinonaktifkan atau dihapus, perbarui atau hapus petunjuk terkait agar tutorial tidak mengarahkan pengguna ke aksi yang tidak tersedia. Fitur khusus admin tidak dimasukkan ke tutorial publik.
- Pertahankan struktur JSON dan penyajian tutorial yang terformat. Jika struktur atau jumlah topik berubah, sesuaikan tampilan, test tutorial, dan keterangan terkait di [`FITUR_APLIKASI.md`](FITUR_APLIKASI.md). Jalankan test tutorial yang terdampak menggunakan filter sesuai aturan Testing.
- Source code dan test yang berlaku menjadi sumber kebenaran. Pembaruan tutorial merupakan bagian dari penyelesaian fitur, bukan pekerjaan lanjutan yang ditunda.

## Klarifikasi Prompt

- Jika prompt pengguna belum memuat konteks yang diperlukan untuk memberikan jawaban atau menjalankan tugas secara akurat, ajukan pertanyaan klarifikasi kepada pengguna sebelum melanjutkan. Pertanyaan harus spesifik pada informasi yang masih kurang dan relevan dengan tugas.

## Testing

- Setiap kode yang baru dibuat atau dimodifikasi harus diuji secara terprogram (programmatic test). Jalankan test dengan filter yang menyasar baris atau fungsi yang diubah; jangan menjalankan seluruh suite tanpa filter.
- Setiap perintah test lokal wajib menggunakan flag `--filter` yang secara spesifik menyasar test, fungsi, atau perilaku yang terdampak. Penyebutan satu atau beberapa path file test saja belum dianggap sebagai filter.
- Jangan menjalankan full test suite di lokal, misalnya `php artisan test` tanpa filter atau flag dan `npm test` tanpa filter. Full suite dijalankan di GitHub Actions atau CI, bukan di mesin lokal.
- Khusus proyek Laravel dengan Pest, kombinasikan `--filter` dengan flag `--tia` (Test Impact Analysis) saat menjalankan test di lokal jika versi Pest yang terpasang sudah v5 atau lebih baru. Jika perintah tersebut menampilkan pesan `TIA does not apply to partial runs — running the selected tests directly.`, ulangi test dengan kombinasi `--filter` dan `--parallel`, lalu gunakan kombinasi itu untuk test lokal berikutnya. Jika Pest masih di bawah v5 dan belum mendukung `--tia`, langsung kombinasikan `--filter` dengan `--parallel`. Periksa versi Pest yang terpasang, misalnya dengan `composer show pestphp/pest`, sebelum memilih flag.
- Hindari penggunaan helper assertSee pada testing, karena kalau eror sulit dicek masalahnya.

## Komentar Kode

- Setiap catatan atau komentar pada kode, baik satu baris maupun blok, harus ditulis dalam Bahasa Indonesia.

## Styling CSS

- Gunakan class Tailwind CSS dan komponen Flowbite untuk styling CSS pada file Blade atau view.
- Hindari menulis CSS custom di luar Tailwind/Flowbite kecuali sangat diperlukan untuk kebutuhan yang tidak tercakup oleh framework.
- Pastikan komponen Flowbite yang digunakan sudah terdaftar di package dependency proyek (`flowbite`).

## Dokumentasi Markdown

- Setiap file Markdown (`.md`) baru harus diletakkan di folder [`docs`](docs), termasuk file rencana atau plan.

## Git

- Jangan melakukan `git commit` kecuali diminta secara eksplisit oleh pengguna. Perubahan boleh dibuat atau diedit di working tree, tetapi commit harus menunggu instruksi langsung.
