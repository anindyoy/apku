# Rencana Optimasi Performa Panel Filament di Produksi

## Acuan dan kondisi aplikasi

Acuan: [panduan deployment Filament 5](https://filamentphp.com/docs/5.x/deployment#improving-filament-panel-performance). Versi terkunci saat rencana dibuat ialah Filament 5.7.6 dan Laravel 13. Panel `admin` memakai penemuan otomatis resource, halaman, dan widget; terdapat banyak komponen di `app/Filament`, sehingga cache indeks komponen relevan untuk diuji. `composer.json` sudah menjalankan `filament:upgrade` setelah autoload diperbarui. Repo hanya memiliki workflow test dan pekerjaan terjadwal; belum ada skrip atau workflow deploy produksi. Target hosting dan cara rilis belum terdokumentasi.

Rencana ini berfokus pada optimasi deployment. Belum ada perubahan fitur pengguna, sehingga `FITUR_APLIKASI.md` dan `resources/content/tutorial.json` tidak perlu diubah saat menyusun rencana.

## Langkah penerapan

1. **Tetapkan proses deploy dan ukur kondisi awal.** Identifikasi server, jenis rilis (direktori tetap atau release terpisah), PHP yang melayani web dan CLI, serta akses untuk mengubah OPcache. Catat waktu respons dan jumlah query untuk login, dashboard, serta beberapa daftar resource dengan akun dan data yang sama. Ukur beberapa kali pada kondisi cache hangat. Ini menjadi pembanding, karena perintah cache Filament tidak menjamin perbaikan besar pada tiap halaman.
2. **Siapkan rilis sebelum cache dibangun.** Instal dependensi produksi dengan Composer dan pertahankan hook `filament:upgrade`; bangun aset frontend dengan `npm ci` dan `npm run build`; siapkan environment, kunci aplikasi, koneksi database, direktori writable, dan migrasi sesuai prosedur deploy yang berlaku. Jangan memakai cache hasil build lokal sebagai artefak produksi jika konfigurasi environment berbeda.
3. **Bangun cache Filament pada setiap rilis.** Setelah seluruh kode, dependensi, dan aset versi baru tersedia, jalankan `php artisan filament:optimize`. Perintah ini mencakup `filament:cache-components` dan `icons:cache`. Karena panel memakai `discoverResources`, `discoverPages`, dan `discoverWidgets`, cache komponen harus dibangun ulang setiap kali kode komponen berubah. Jangan mengaktifkannya pada proses pengembangan lokal biasa.
4. **Bangun cache Laravel untuk environment produksi.** Jalankan `php artisan optimize` sesudah environment final tersedia. Verifikasi khususnya `route:cache` pada route dan paket yang terpasang; bila ada kegagalan, perbaiki penyebabnya sebelum rilis. Jangan menghapus langkah cache diam-diam. Pastikan cache baru dibangun pada setiap release, sebelum trafik dialihkan bila memakai release terpisah.
5. **Aktifkan dan periksa OPcache pada PHP web.** Periksa `opcache.enable` pada PHP CLI seperti contoh panduan, lalu konfirmasi juga konfigurasi PHP-FPM atau web SAPI karena nilainya bisa berbeda. Aktifkan OPcache melalui konfigurasi server jika belum aktif, atur invalidasi atau restart PHP-FPM saat pergantian release, dan dokumentasikan prosedurnya. Perubahan ini berada di infrastruktur, bukan pada kode aplikasi.
6. **Verifikasi rilis.** Pastikan login, dashboard, navigasi resource, ikon, halaman `/tutorial`, dan aset frontend tampil benar; bandingkan hasil pengukuran dengan baseline. Periksa log untuk komponen tidak ditemukan, ikon hilang, kesalahan route, atau file cache yang tidak dapat ditulis. Jalankan test terfilter yang menyasar perubahan kode atau skrip deploy sesuai aturan `AGENTS.md`; bila hanya konfigurasi server yang berubah, gunakan pemeriksaan runtime dan smoke test produksi/staging.

## Urutan perintah yang diusulkan

Urutan inti setelah instalasi dependensi, build aset, penyiapan environment, dan migrasi:

```sh
php artisan filament:optimize
php artisan optimize
```

Skrip `scripts/release-optimize.php` menjalankan kedua perintah tersebut secara berurutan dan berhenti ketika salah satunya gagal. Jalankan dengan binary PHP 8.4+ yang dipakai hosting: `/path/to/php scripts/release-optimize.php`. Skrip memeriksa `.env`, `vendor/autoload.php`, `public/build/manifest.json`, `APP_ENV=production`, dan `APP_DEBUG=false` sebelum mengubah cache. Gunakan `--dry-run` untuk memeriksa urutan perintah tanpa membangun cache. Jalankan skrip setelah Composer, build aset, konfigurasi environment, dan migrasi selesai, sebelum trafik diarahkan ke rilis baru. Hook `filament:upgrade` di Composer tetap dipertahankan; jika instalasi memakai `--no-scripts`, jalankan `php artisan filament:upgrade` secara eksplisit sebelum skrip optimasi.

### Pengukuran dan pemeriksaan rilis

Simpan hasil baseline dan hasil sesudah rilis di catatan deploy, dengan waktu, commit, versi PHP, jumlah data, akun uji, dan keadaan cache yang sama. Untuk setiap URL login, dashboard, dan daftar resource, lakukan lima permintaan setelah satu permintaan pemanasan, lalu catat median `time_total` dan status HTTP dengan `curl -sS -o /dev/null -w '%{http_code} %{time_total}\n'`. Halaman yang memerlukan login harus memakai cookie akun uji yang sah; jangan mencatat cookie di log atau repo. Bandingkan median sebelum dan sesudah, bukan satu permintaan acak. Hitung query hanya bila profiler tersedia di staging; jangan aktifkan profiler publik di produksi.

Periksa login, dashboard, beberapa daftar resource, ikon, `/tutorial`, dan berkas dari manifest Vite. Pastikan tidak ada error route, komponen, ikon, atau cache di log. Verifikasi document root ke `public/`, PHP CLI dan PHP web minimal 8.4, serta `opcache.enable=1` pada **web SAPI**; hasil `php -r` hanya mewakili CLI. Catat path PHP, cron, worker `import-transaksi`, dan cara invalidasi OPcache setelah paket hosting dipilih.

## Pemulihan dan kriteria selesai

Jika cache Filament menyebabkan masalah setelah rilis, jalankan `php artisan filament:optimize-clear`, lalu bangun ulang setelah penyebab diperbaiki. Untuk cache Laravel, gunakan `php artisan optimize:clear`, lalu bangun ulang dengan konfigurasi yang benar. Pada deploy berbasis release terpisah, kembalikan symlink atau trafik ke release sebelumnya sesuai prosedur hosting; jangan mengandalkan cache lama dari release baru.

Selesai jika proses deploy menjalankan optimasi setiap rilis, OPcache terverifikasi pada web SAPI, halaman penting lolos smoke test, tidak ada error terkait cache atau aset, dan hasil pengukuran sebelum/sesudah tercatat. Pemilihan skrip atau workflow deploy final menunggu informasi paket hosting dan mekanisme rilis.

## Penyesuaian untuk hPanel Hostinger dan cPanel

Kedua opsi dapat memakai perintah optimasi yang sama. Perbedaan utamanya ialah akses shell, pilihan PHP, kontrol OPcache, dan cara menjalankan pekerjaan terjadwal. Sebelum memilih server, cocokkan butir berikut pada **paket hosting yang akan dipakai**, bukan hanya nama panelnya.

| Pemeriksaan | hPanel Hostinger | cPanel |
| --- | --- | --- |
| PHP | Pilih PHP 8.4 atau lebih baru yang kompatibel di **PHP Configuration**. Samakan versi PHP website dan CLI; versi CLI dapat mengikuti versi default paket, bukan versi domain. | Pastikan PHP 8.4 tersedia untuk domain di **MultiPHP Manager** dan untuk CLI. Ketersediaan versi dan PHP-FPM dapat dibatasi penyedia hosting. |
| Shell dan Composer | Pastikan paket menyediakan SSH dan Composer 2. Pada Hostinger Web/Cloud, SSH tergantung paket dan dibatasi pada direktori akun. | Pastikan penyedia mengaktifkan Terminal/SSH dan Composer. Jangan menganggap fitur cPanel otomatis berarti ada shell atau izin menjalankan seluruh perintah deploy. |
| OPcache | Periksa dan aktifkan ekstensi OPcache melalui **PHP Configuration → PHP extensions**. Cache Manager LiteSpeed di hPanel adalah lapisan terpisah. | Periksa ekstensi OPcache untuk versi PHP domain dan statusnya pada web SAPI. Jika menu atau pengaturannya tidak tersedia, minta penyedia hosting memeriksanya. PHP-FPM, bila tersedia, dikendalikan melalui MultiPHP Manager/WHM. |
| Proses latar | hPanel menyediakan Cron Jobs; kemampuan menjalankan proses persisten pada Web/Cloud lebih terbatas daripada VPS. | cPanel menyediakan Cron Jobs jika diaktifkan oleh penyedia; proses persisten dan PHP-FPM bergantung pada kebijakan penyedia. |

### Keputusan sebelum deploy

1. **Verifikasi prasyarat PHP 8.4.** `composer.json` mensyaratkan `^8.4`. Periksa PHP web dan PHP CLI yang akan menjalankan Composer, Artisan, dan cron. Jika salah satunya hanya PHP 8.3, paket itu belum cocok tanpa perubahan versi aplikasi.
2. **Pilih cara mengeksekusi perintah rilis.** Dengan SSH, jalankan Composer dan Artisan di root Laravel menggunakan binary PHP 8.4 yang sama dengan website. Tanpa SSH, siapkan artefak build di luar server dan mekanisme eksekusi Artisan yang aman; jangan membuat endpoint HTTP publik untuk menjalankan perintah optimasi. Jika tidak ada cara menjalankan Artisan di server, opsi hosting tersebut belum memenuhi rencana ini.
3. **Pastikan document root mengarah ke `public/`.** Simpan `.env`, `vendor/`, dan source di luar document root. Jika panel hosting tidak mengizinkan pengubahan document root, rancang tata letak deploy yang setara dan uji bahwa file privat tidak dapat diakses melalui URL.
4. **Rencanakan antrean aplikasi.** `.env.example` memakai `QUEUE_CONNECTION=database`, dan impor transaksi besar menggunakan antrean `import-transaksi`. Pastikan tersedia mekanisme worker yang andal pada paket pilihan. Cron yang menjalankan `queue:work --stop-when-empty` secara berkala dapat dipertimbangkan pada shared hosting, tetapi ukur keterlambatan serta cegah proses tumpang tindih; untuk kebutuhan worker yang terus aktif, pilih paket yang mendukung proses persisten/VPS.
5. **Pisahkan cache aplikasi dari cache halaman web.** Jangan mengaktifkan cache halaman penuh LiteSpeed/CDN untuk respons panel `/admin` yang memuat sesi dan data pengguna. Jika memakai Cache Manager Hostinger, atur pengecualian untuk route dinamis dan uji hasilnya; optimasi Filament serta OPcache tetap perlu berjalan terpisah.

Setelah paket dan aksesnya diketahui, tambahkan runbook deploy yang spesifik untuk hPanel atau cPanel, termasuk path PHP/Composer/Artisan, pengaturan cron, dan cara invalidasi OPcache yang memang tersedia pada akun tersebut.

### Referensi penyedia

- [Hostinger: PHP extensions dan OPcache](https://www.hostinger.com/support/which-php-extensions-and-configuration-options-are-supported-at-hostinger/), [akses SSH](https://support.hostinger.com/en/articles/1583645-how-to-enable-ssh-access), [perbedaan PHP CLI dan website saat memakai Composer](https://www.hostinger.com/support/5792082-how-to-solve-common-composer-issues-at-hostinger/), [Cache Manager](https://www.hostinger.com/support/6215624-how-to-use-cache-manager-at-hostinger/), [kemampuan proses latar](https://www.hostinger.com/support/which-server-capabilities-are-supported-at-hostinger/).
- [cPanel: MultiPHP Manager](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/), [Cron Jobs](https://docs.cpanel.net/cpanel/advanced/cron-jobs/), [pengaturan PHP](https://docs.cpanel.net/ea4/php/about-php/).
