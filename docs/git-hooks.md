# Pembaruan dependency dan migrasi otomatis setelah git pull

Aktifkan hook sekali pada setiap clone dari root proyek:

```sh
git config --local core.hooksPath .githooks
```

Pada Linux/macOS, pastikan hook dapat dieksekusi:

```sh
chmod +x .githooks/post-merge .githooks/post-rewrite
```

Hook `post-merge` menangani pull fast-forward dan merge. Hook `post-rewrite` menangani rebase yang menulis ulang commit. Keduanya membandingkan keadaan sebelum dan sesudah integrasi; jika ada file PHP baru di `database/migrations`, hook menjalankan `php artisan migrate --force --no-interaction` dari root proyek. Penambahan beberapa migration hanya memicu satu pemanggilan Artisan. Perubahan atau penghapusan migration lama dan pull tanpa perubahan tidak memicu migrasi.

Jika `composer.json` atau `composer.lock` berubah, hook menjalankan `composer2 update --no-interaction` sekali dari root proyek, sebelum migrasi. Perintah ini memperbarui dependency sesuai batas versi di `composer.json` dan dapat mengubah `composer.lock`. Jika Composer gagal, migrasi otomatis tidak dijalankan; perbaiki penyebabnya lalu jalankan ulang `composer2 update` serta migrasi jika diperlukan.

PHP dan `composer2` harus tersedia di PATH, dependency Composer sudah terpasang, dan konfigurasi database aplikasi sudah siap. Flag `--force` memungkinkan migrasi otomatis termasuk pada production tanpa konfirmasi. Artisan menjalankan seluruh migration yang masih pending pada database yang dikonfigurasi aplikasi.

Git tidak menyediakan hook khusus setelah pull. Karena itu merge dan rebase manual juga dapat memicu migrasi; amend commit tidak memicunya. Jika migrasi gagal, hook menampilkan pesan kesalahan, tetapi integrasi Git yang sudah selesai tidak dibatalkan. Perbaiki penyebabnya dan jalankan `php artisan migrate` kembali.

Konfigurasi hook bersifat lokal dan tidak ikut dikloning. Untuk menonaktifkannya:

```sh
git config --local --unset core.hooksPath
```
