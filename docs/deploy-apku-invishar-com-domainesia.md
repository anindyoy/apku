# Deploy APKu ke apku.invishar.com di Domainesia

Panduan ini untuk aplikasi Laravel 13 + Filament 5 (PHP ^8.4) ke subdomain `apku.invishar.com` di shared hosting Domainesia (cPanel, LiteSpeed/Apache).

> Struktur proyek acuan: [`composer.json`](../composer.json:1), [`.env.example`](../.env.example:1), [`public/.htaccess`](../public/.htaccess:1), [`public/index.php`](../public/index.php:1), [`scripts/release-optimize.php`](../scripts/release-optimize.php:1).

## 0. Prasyarat

1. Domain `invishar.com` sudah diarahkan ke nameserver/hosting Domainesia.
2. Login cPanel Domainesia bisa (dari Client Area Domainesia > Services > cPanel Login).
3. Paket hosting mendukung:
   - PHP 8.4 (wajib, karena `laravel/framework ^13` butuh PHP 8.4)
   - MySQL/MariaDB, Cron Jobs, Terminal/SSH, SSL AutoSSL/Let's Encrypt
4. Catat username cPanel, misal `invishar`, home dir `/home/invishar`.

Jika PHP 8.4 belum tersedia di `Select PHP Version`, hubungi support Domainesia / upgrade paket sebelum lanjut. Jangan paksakan jalan di PHP 8.3, composer akan gagal.

## 1. Buat subdomain apku.invishar.com

1. Login cPanel.
2. Buka menu `Domains` (tema Jupiter baru) atau `Subdomains` (tema Paper Lantern lama).
   - Jupiter: `Domains > Create A New Domain` > isi `apku.invishar.com`, hilangkan centang `Share document root` agar punya document root sendiri.
   - Paper Lantern: `Subdomains >` isi Subdomain `apku`, Domain `invishar.com`.
3. Isi Document Root sementara, contoh:
   - `/home/invishar/apku.invishar.com` atau
   - `/home/invishar/public_html/apku`
   Catat path ini.
4. Klik Create/Submit.
5. Tunggu 5-30 menit propagasi DNS. Cek dari laptop:
   - `nslookup apku.invishar.com`
   - `ping apku.invishar.com`
   Harus mengarah ke IP hosting Domainesia.

Untuk Laravel, document root akhir harus menunjuk ke folder `public` proyek, misal `/home/invishar/apku/public`. Ini disetelah upload (lihat Bagian 4).

## 2. Set PHP 8.4 dan ekstensi

1. cPanel > `Select PHP Version` (CloudLinux).
2. Pilih `8.4` untuk domain/subdomain tersebut (Apply + Set as current, atau via `MultiPHP Manager > apku.invishar.com > PHP 8.4`).
3. Aktifkan ekstensi wajib Laravel + Filament:
   - `bcmath, ctype, curl, fileinfo, gd, iconv, intl, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, zip, exif`
4. Opsi `upload_max_filesize` dan `post_max_size` naikkan ke `20M` atau lebih jika ada fitur import Excel.
5. Simpan.

Verifikasi via Terminal cPanel:
```bash
/usr/local/bin/php -v
/usr/local/bin/php -m | grep -i pdo_mysql
```

## 3. Buat database MySQL/MariaDB

1. cPanel > `MySQL Databases`.
2. Buat Database, contoh `invishar_apku`.
3. Buat User, contoh `invishar_apkuuser` + password kuat.
4. `Add User To Database` > centang `ALL PRIVILEGES`.
5. Catat:
   - `DB_HOST=localhost` (umumnya di Domainesia `localhost`, bukan `127.0.0.1`)
   - `DB_PORT=3306`
   - `DB_DATABASE=invishar_apku`
   - `DB_USERNAME=invishar_apkuuser`
   - `DB_PASSWORD=...`
6. Opsional: buka `phpMyAdmin` untuk memastikan DB kosong dan collation `utf8mb4_unicode_ci`.

Proyek default memakai `DB_CONNECTION=mariadb` di [`.env.example`](../.env.example:26). Di shared hosting MySQL/MariaDB, aman memakai `mysql` (kompatibel). Gunakan `mysql` kecuali Anda yakin driver `mariadb` tersedia.

## 4. Siapkan build lokal

Lakukan di laptop sebelum upload:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Pastikan muncul [`public/build/manifest.json`](../public/build/manifest.json:1). File ini wajib ada, jika tidak halaman akan putih/error Vite.

Hapus `.env` lokal dari zip, jangan ikutkan `node_modules`, `.git`, `tests`, `storage/logs/*`.

Buat arsip `apku.zip` berisi seluruh proyek (termasuk `vendor/` jika hosting tidak ada composer/SSH; jika ada Terminal, `vendor/` boleh tidak ikut lalu `composer install` di server).

## 5. Upload dan atur Document Root

Pola yang benar untuk shared hosting:

```
/home/invishar/apku/           <- seluruh proyek Laravel (app, bootstrap, config, vendor, .env, artisan, dst)
/home/invishar/apku/public/    <- document root subdomain
```

Langkah:

1. cPanel > `File Manager` > buat folder `/home/invishar/apku`.
2. Upload `apku.zip` ke folder itu > `Extract`.
3. cPanel > `Domains > Manage apku.invishar.com > Document Root` > ubah ke `/home/invishar/apku/public` (atau path ekuivalen `apku.invishar.com/public` sesuai struktur Anda). Save.
4. Pastikan [`public/.htaccess`](../public/.htaccess:1) ikut terupload (File Manager > Settings > centang Show Hidden Files).
5. Set permission:
   - folder `755`, file `644`
   - `storage/` dan `bootstrap/cache/` writable (`775`), bisa via File Manager > Permissions atau:
```bash
chmod -R 775 storage bootstrap/cache
```

Jangan menaruh isi `public/` langsung ke `public_html/` lalu mengedit [`public/index.php`](../public/index.php:1) kecuali terpaksa. Cara symlink/document-root di atas lebih aman dan tidak merusak `__DIR__.'/../vendor/autoload.php'`.

## 6. Konfigurasi .env produksi

Di File Manager/Terminal, copy `.env.example` menjadi `.env`, lalu isi:

```ini
APP_NAME=APKu
APP_ENV=production
APP_DEBUG=false
APP_URL=https://apku.invishar.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=invishar_apku
DB_USERNAME=invishar_apkuuser
DB_PASSWORD=isi_password_db

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_LEVEL=error

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@invishar.com"
MAIL_FROM_NAME="APKu"

ADMIN_PASSWORD=isi_password_admin_awal_yang_kuat

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
TURNSTILE_HOSTNAMES=apku.invishar.com
```

Lalu generate key dan optimasi via Terminal cPanel (masuk ke `/home/invishar/apku`):

```bash
/usr/local/bin/php -v
/usr/local/bin/php artisan key:generate --force
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan storage:link
/usr/local/bin/php artisan filament:optimize
/usr/local/bin/php artisan optimize
```

Atau validasi ketat memakai script rilis proyek:

```bash
/usr/local/bin/php scripts/release-optimize.php
```

Script tersebut menolak jalan jika `APP_ENV` bukan `production`, `APP_DEBUG` bukan `false`, atau `vendor/` dan `manifest.json` belum siap. Lihat [`scripts/release-optimize.php`](../scripts/release-optimize.php:1).

Catatan admin awal: migrasi `2026_09_05_000002_create_admin_user.php` memakai `ADMIN_PASSWORD`. Jika user admin sudah ada, ganti manual via tinker atau panel admin, jangan re-seed demo (`DemoFiturSeeder`, `TransaksiSeeder`, dll hanya untuk lokal).

## 7. SSL dan Force HTTPS

1. cPanel > `SSL/TLS Status` > pilih `apku.invishar.com` > `Run AutoSSL`. Tunggu Valid.
   - Alternatif: `Security > Let's Encrypt > Issue` untuk subdomain tersebut.
2. cPanel > `Domains > apku.invishar.com > Force HTTPS Redirect > ON`.
3. Pastikan `APP_URL` memakai `https://`, bukan `http://`.

## 8. Cron untuk scheduler dan antrean

Aplikasi memakai `QUEUE_CONNECTION=database` dan job `ProsesImportTransaksi`, jadi butuh scheduler + queue worker. Shared hosting tidak ada Supervisor, pakai Cron.

cPanel > `Cron Jobs` > `Add New Cron Job` > `Once Per Minute`:

```bash
/usr/local/bin/php /home/invishar/apku/artisan schedule:run >> /dev/null 2>&1
```

Tambah satu lagi untuk antrean (pilih salah satu):

```bash
* * * * * /usr/local/bin/php /home/invishar/apku/artisan queue:work --stop-when-empty --tries=1 --timeout=300 --queue=import-transaksi,default >> /dev/null 2>&1
```

Cek path PHP dulu dengan `which php` atau `whereis php`; di Domainesia umumnya `/usr/local/bin/php` atau `/usr/bin/php`. Sesuaikan username dan path `artisan`.

Jangan jalankan `queue:listen` persisten di shared hosting, pakai `queue:work --stop-when-empty` via cron agar tidak melebihi limit proses.

## 9. Verifikasi pasca-deploy

1. Buka `https://apku.invishar.com` > harus redirect HTTPS, tidak 500/403.
2. Buka `/admin` > login admin > cek dashboard Filament.
3. Cek aset: tidak ada 404 di `public/build/`; jika putih, rebuild lokal dan re-upload `public/build/`.
4. Cek upload: coba upload/import file kecil > pastikan `storage/app/public` ter-link ke `public/storage`.
5. Cek log jika error: `storage/logs/laravel.log` via File Manager/Terminal.
6. Cek antrean: `jobs` dan `job_batches` terisi saat import, lalu habis setelah cron jalan.
7. Cek mail log, Turnstile (jika diaktifkan), dan zona waktu `Asia/Jakarta`.

Troubleshooting cepat:
- `500`: cek versi PHP 8.4, `.env` (`APP_KEY` kosong?), permission `storage/`, `config/database.php` kredensial salah.
- `Vite manifest not found`: belum `npm run build`.
- `SQLSTATE Access denied`: user DB belum di-attach ke DB atau password salah.
- `Session table not found`: pastikan `migrate --force` sukses (tabel `sessions`, `cache`, `jobs` dari `0001_01_01_000001_create_cache_table.php` dan `0001_01_01_000002_create_jobs_table.php`).
- `Storage link exists`: hapus `public/storage` yang rusak lalu `storage:link` ulang.

## 10. Update berikutnya

Untuk update kode:

```bash
# maintenance
/usr/local/bin/php artisan down
# upload file baru (jangan timpa .env)
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan filament:optimize
/usr/local/bin/php artisan optimize
/usr/local/bin/php artisan up
```

Jangan `git commit` otomatis dari server kecuali diminta. Perubahan cukup di working tree lalu deploy manual.
