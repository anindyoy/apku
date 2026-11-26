# Rencana: Ganti Semua URL `/admin` Menjadi `/app`

Dokumen ini menjelaskan langkah-langkah yang harus dilakukan untuk memindahkan seluruh URL panel aplikasi dari prefiks `/admin` menjadi `/app` (contoh: `/admin` menjadi `/app`, `/admin/transaksi` menjadi `/app/transaksi`).

Dokumen ini adalah **rencana**. Implementasi belum dilakukan pada pekerjaan yang menghasilkan dokumen ini.

## 1. Tujuan dan Ruang Lingkup

### 1.1 Tujuan

- Seluruh URL yang tampil di browser (dashboard, transaksi, kas, sumber dana, laporan, langganan, pengaturan, autentikasi, verifikasi email, dan seterusnya) memakai prefiks `/app`.
- Tautan lama `/admin/...` yang sudah tersebar (bookmark, email, notifikasi Telegram, tautan di tutorial, invoice) tetap dapat dibuka tanpa error 404 melalui redirect permanen.
- Nama route Filament ikut berganti dari `filament.admin.*` menjadi `filament.app.*` agar konsisten dengan path baru.
- Istilah peran **Admin** (role pengguna) tidak berubah. Yang berubah hanya prefiks URL dan nama panel, bukan hak akses atau nama class.

### 1.2 Batasan yang Harus Diputuskan Sebelum Implementasi

| Pertanyaan | Opsi | Rekomendasi |
| --- | --- | --- |
| Apakah `/admin` tetap harus bisa diakses? | (a) Redirect permanen `/admin/*` ke `/app/*`; (b) tidak sama sekali | (a) redirect 301 agar bookmark lama dan tautan di email tidak rusak |
| Apakah nama route Filament `admin` diganti menjadi `app`? | (a) dipertahankan; (b) diganti | **(b) diganti**, sesuai keputusan pengguna; semua referensi `filament.admin.*` harus diperbarui menjadi `filament.app.*` |
| Apakah URL lama di audit `storage`/log produksi perlu dibersihkan? | (a) abaikan; (b) bersihkan | (a) cukup; data `storage/` bukan bagian aplikasi |
| Apakah tutorial pengguna menyebut URL eksplisit? | perlu diperiksa | periksa `resources/content/tutorial.json` dan perbarui bila ada |

### 1.3 Di Luar Ruang Lingkup

- Perubahan hak akses, nama menu, atau label fitur.
- Perubahan skema database.
- Penggantian kata "Admin" pada label peran pengguna (`role = admin`, `isAdmin()`).
- Perubahan struktur `FITUR_APLIKASI.md` selain penyesuaian prefiks URL di dalam teksnya.

## 2. Kondisi Saat Ini (Temuan Pemetaan)

Aplikasi memakai satu panel Filament 5 yang didefinisikan di `app/Providers/Filament/AdminPanelProvider.php`:

```php
->default()
->id('admin')
->path('admin')
```

Karena semua resource, page, dan cluster berada di bawah panel tersebut, **sebagian besar URL berubah otomatis** hanya dengan mengubah `->path('admin')` menjadi `->path('app')`. Namun masih terdapat URL yang ditulis manual (hard-coded) sehingga tidak ikut berubah.

### 2.1 Titik yang Berubah Otomatis

Mengubah `->path('app')` akan mengubah seluruh URL berikut beserta turunannya:

| URL lama | URL baru |
| --- | --- |
| `/admin` | `/app` |
| `/admin/login`, `/admin/register`, `/admin/password-reset/*` | `/app/login`, `/app/register`, `/app/password-reset/*` |
| `/admin/email-verification/*` | `/app/email-verification/*` |
| `/admin/transaksi` | `/app/transaksi` |
| `/admin/pengaturan/*` | `/app/pengaturan/*` |
| `/admin/langganans` | `/app/langganans` |
| `/admin/utangs/*`, `/admin/piutangs/*` | `/app/utangs/*`, `/app/piutangs/*` |
| `/admin/onboarding` | `/app/onboarding` |

Nama route seperti `filament.admin.auth.email-verification.prompt` dan `filament.admin.resources.transaksi.index` **ikut berubah** menjadi `filament.app...` karena `->id('admin')` diganti menjadi `->id('app')` sesuai keputusan bagian 1.2. Seluruh referensi nama route di source, view, dan test harus diperbarui (daftar lengkap pada bagian 3.1).

### 2.2 Titik yang Ditulis Manual dan Wajib Disunting

Berkas-berkas berikut memuat prefiks `/admin` secara literal sehingga harus diperbarui satu per satu:

| Berkas | Baris | Isi | Tindakan |
| --- | --- | --- | --- |
| `routes/web.php` | 12-13 | `Route::permanentRedirect('/admin/audit-saldo-dompet', '/admin/cocokkan-saldo')` dan `Route::permanentRedirect('/admin/riwayat-pencocokan', '/admin/cocokkan-saldo')` | Arahkan redirect lama ke `/app/cocokkan-saldo` |
| `routes/web.php` | 35 | `url('/admin/email-verification/verify/...')` pada preview email lokal | Ganti ke `/app/email-verification/...` |
| `app/Filament/Resources/PiutangResource/Pages/PiutangDetail.php` | 99 | `redirect(url('/admin/'.$tipe.'s'))` | Ganti ke `/app/`. Kalau memungkinkan, gunakan `route()` atau `getUrl()` untuk menghindari hard-code |
| `app/Filament/Resources/UtangResource/Pages/UtangDetail.php` | 114 | `redirect(url('/admin/'.$tipe.'s'))` | Sama seperti di atas |
| `app/Filament/Resources/UserResource.php` | 109 | `->redirectTo('/admin')` (aksi impersonate) | Ganti ke `/app` atau `filament()->getUrl()` |
| `app/Models/UtangPiutang.php` | 55-56 | `url('/admin/utangs/.../detail')` dan `url('/admin/piutangs/.../detail')` | Ganti ke `/app/`, sebaiknya memakai `UtangResource::getUrl()` / `PiutangResource::getUrl()` |
| `app/Notifications/InvoiceOrderNotification.php` | 52, 57 | `url('/admin/langganans')` | Ganti ke `/app/langganans` atau `LanggananResource::getUrl()` |
| `app/Notifications/PembayaranDisetujuiNotification.php` | 67, 72 | `url('/admin')` | Ganti ke `/app` atau `filament()->getUrl()` |
| `app/Notifications/PembayaranDitolakNotification.php` | 72, 78 | `url('/admin/langganans')` | Ganti ke `/app/langganans` atau `getUrl()` |
| `app/Notifications/TrialPremiumAktif.php` | 33 | `url('/admin')` | Ganti ke `/app` atau `filament()->getUrl()` |
| `app/Notifications/TrialPremiumBerakhir.php` | 37 | `url('/admin/langganans')` | Ganti ke `/app/langganans` |
| `app/Notifications/TrialPremiumPengingat.php` | 39 | `url('/admin/langganans')` | Ganti ke `/app/langganans` |
| `app/Jobs/KirimNotifTelegramPembayaran.php` | 101 | fallback `config('app.url').'/admin/langganans'` | Ganti ke `/app/langganans` |

Temuan tambahan yang perlu diperiksa ulang saat implementasi (pola pencarian `/admin` dan `admin/` sempat terpotong pada beberapa hasil):

- Seluruh isi `app/Notifications/`, `app/Jobs/`, `app/Console/`, dan `app/Livewire/` yang menyusun URL manual.
- `app/Http/Controllers/` (`BeliPaketController`, `LanggananInvoiceController`, `KasPublikController`, `HomeController`) yang memanggil `filament()->getUrl()`; pemanggilan ini otomatis aman, tetapi pastikan tidak ada string `/admin` literal.
- `resources/views/**` (Blade dan komponen Filament) yang menulis `href` atau `url()` manual.
- `resources/content/tutorial.json`: periksa apakah memuat URL eksplisit yang dibaca pengguna.
- `lang/**`: periksa pesan yang memuat `/admin`.

## 3. Langkah Implementasi

Ikuti urutan berikut. Kerjakan dalam satu perubahan yang sama dan jalankan test terkait di akhir (bukan setelah tiap suntingan kecil).

### 3.1 Ubah Path Panel (Sumber Utama URL)

Berkas: `app/Providers/Filament/AdminPanelProvider.php`

Ubah `->path('admin')` menjadi `->path('app')`.

Sesuai keputusan pada bagian 1.2, **`->id('admin')` juga diganti menjadi `->id('app')`**. Perubahan ini mengubah seluruh nama route `filament.admin.*` menjadi `filament.app.*`, sehingga semua referensi berikut wajib diperbarui dalam pekerjaan yang sama:

| Berkas | Referensi lama | Perubahan |
| --- | --- | --- |
| `app/Http/Middleware/EnsureUserHasCompletedOnboarding.php` | `$request->routeIs('filament.admin.pages.onboarding')` dan `$request->routeIs('filament.admin.auth.*')` | menjadi `filament.app.pages.onboarding` dan `filament.app.auth.*` |
| `app/Http/Controllers/BeliPaketController.php` | `route('filament.admin.auth.register')` | menjadi `filament.app.auth.register` |
| `app/Providers/AppServiceProvider.php` | `route('filament.admin.auth.login')` pada `Authenticate::redirectUsing` | menjadi `filament.app.auth.login` |
| `app/Jobs/KirimNotifTelegramPembayaran.php` | `route('filament.admin.resources.langganans.index')` | menjadi `filament.app.resources.langganans.index` |
| `resources/views/welcome.blade.php` | 8 kemunculan `filament.admin.*` (tautan login/register/dasbor pada landing page) | seluruhnya menjadi `filament.app.*` |
| `tests/Feature/Filament/ManageVoucherCodesTest.php` | `Route::has('filament.admin.resources.voucher-codes.index')` dan `Route::has('filament.admin.resources.vouchers.codes')` (ekspektasi false) | menjadi `filament.app.*`; nilai ekspektasi tetap false |
| `tests/Feature/Filament/OnboardingTest.php` | `route('filament.admin.auth.email-verification.prompt')` (2 kemunculan) | menjadi `filament.app.auth.email-verification.prompt` |
| `tests/Feature/BeliPaketTest.php` | referensi `filament.admin.*` | menjadi `filament.app.*` |
| `tests/Feature/LandingPageTest.php` | 2 kemunculan `filament.admin.*` | menjadi `filament.app.*` |
| `tests/Feature/LanggananFeatureTest.php` | 7 kemunculan `filament.admin.*` | menjadi `filament.app.*` |
| `tests/Feature/PasswordResetTest.php` | `route('filament.admin.auth.password-reset.request')` | menjadi `filament.app.auth.password-reset.request` |
| `tests/Feature/TrialLandingTest.php` | referensi `filament.admin.*` | menjadi `filament.app.*` |
| `tests/Feature/VoucherLanggananTest.php` | 2 kemunculan `filament.admin.*` | menjadi `filament.app.*` |

Dampak tambahan yang perlu diantisipasi saat `id` berubah:

- Penyimpanan preferensi widget dashboard Filament (visibilitas dan urutan bagian dashboard per akun) memakai kunci berdasarkan identitas panel; verifikasi apakah preferensi lama tetap terbaca atau perlu reset.
- Cache konfigurasi/route produksi wajib dibersihkan setelah deploy.
- Filter test berbasis nama route yang menyebut `admin` harus ditelusuri ulang.

### 3.2 Perbarui URL yang Ditulis Manual

Ganti setiap kemunculan literal `/admin` (bukan nama route `filament.admin.*`) menjadi `/app`, atau utamakan helper bawaan:

| Berkas | Perubahan |
| --- | --- |
| `app/Filament/Resources/PiutangResource/Pages/PiutangDetail.php` | Redirect `url('/admin/'.$tipe.'s')` diarahkan ke `/app/`; jika memungkinkan gunakan route resource |
| `app/Filament/Resources/UtangResource/Pages/UtangDetail.php` | Sama seperti di atas |
| `app/Filament/Resources/UserResource.php` | `->redirectTo('/admin')` (impersonate) menjadi `/app` atau `filament()->getUrl()` |
| `app/Models/UtangPiutang.php` | Link detail `/admin/utangs/...` dan `/admin/piutangs/...` menjadi `/app/...`; pertimbangkan `UtangResource::getUrl()` dan `PiutangResource::getUrl()` |
| `app/Notifications/InvoiceOrderNotification.php` | `url('/admin/langganans')` menjadi `/app/langganans` |
| `app/Notifications/PembayaranDisetujuiNotification.php` | `url('/admin')` menjadi `/app` |
| `app/Notifications/PembayaranDitolakNotification.php` | `url('/admin/langganans')` menjadi `/app/langganans` |
| `app/Notifications/TrialPremiumAktif.php` | `url('/admin')` menjadi `/app` |
| `app/Notifications/TrialPremiumBerakhir.php` | `url('/admin/langganans')` menjadi `/app/langganans` |
| `app/Notifications/TrialPremiumPengingat.php` | `url('/admin/langganans')` menjadi `/app/langganans` |
| `app/Jobs/KirimNotifTelegramPembayaran.php` | fallback `config('app.url').'/admin/langganans'` menjadi `/app/langganans` |

Daftar ini merupakan pemetaan awal. Saat implementasi, cari ulang `/admin` dan `admin/` di `app`, `routes`, `resources`, `lang`, `config`, `docs`, dan `tests`; abaikan `storage`, `vendor`, serta `node_modules`. Periksa khusus semua file `app/Notifications/`, `app/Jobs/`, `app/Console/`, `app/Livewire/`, Blade, `resources/content/tutorial.json`, dan `lang/**`.

### 3.3 Perbarui Redirect Lama dan Tambahkan Kompatibilitas

Di `routes/web.php`, arahkan target redirect slug lama ke path baru:

```php
Route::permanentRedirect('/admin/audit-saldo-dompet', '/app/cocokkan-saldo');
Route::permanentRedirect('/admin/riwayat-pencocokan', '/app/cocokkan-saldo');
```

Ubah preview email lokal dari `/admin/email-verification/...` menjadi `/app/email-verification/...`.

Tambahkan redirect kompatibilitas untuk bookmark, invoice, email, dan pesan Telegram lama:

```php
// Redirect kompatibilitas dari prefiks URL lama /admin ke /app.
Route::permanentRedirect('/admin', '/app');
Route::permanentRedirect('/admin/{any}', '/app/{any}')->where('any', '.*');
```

Letakkan redirect slug spesifik sebelum redirect umum. Verifikasi bahwa redirect umum tidak menelan slug lama yang perlu diarahkan ke `/app/cocokkan-saldo`. Jika 301 dianggap terlalu agresif saat rollout, gunakan 302 sementara.

### 3.4 Perbarui Test

Perbarui ekspektasi URL pada file berikut:

- `tests/Feature/EmailNotifikasiPembayaranTest.php` — URL `/admin` dan `/admin/langganans` menjadi `/app` dan `/app/langganans`.
- `tests/Feature/EmailVerifikasiTest.php` — URL verifikasi menjadi `/app/email-verification/...`.
- `tests/Feature/PasswordResetTest.php` — `/admin/login` dan `/admin/password-reset/request` menjadi `/app/...`.
- `tests/Feature/TutorialTest.php` — periksa string `admin` pada test JavaScript; jangan mengganti buta kasus negatif seperti domain eksternal atau `administrator`.
- `tests/Feature/Filament/SettingNavigationTest.php` — prefix ekspektasi `/app/pengaturan/`.
- `tests/Feature/Filament/ShareBukuResourceTest.php` — URL kolaborator dan assertion 404 memakai `/app`.
- `tests/Feature/Filament/UtangPiutangActionLabelTest.php` — URL detail memakai `/app`.

Tambahkan test bila belum ada untuk memastikan:

- `/app` dapat diakses sesuai autentikasi dan hak akses yang berlaku.
- `/admin` redirect ke `/app`.
- `/admin/transaksi` redirect ke `/app/transaksi`.
- URL lama slug pencocokan saldo diarahkan ke `/app/cocokkan-saldo`.
- nama route `filament.admin.*` tetap tersedia.

### 3.5 Perbarui Dokumentasi

- `FITUR_APLIKASI.md`: ubah referensi eksplisit seperti `/admin/email-verification/prompt` menjadi `/app/email-verification/prompt`; cari seluruh `/admin` lain.
- `docs/PLAN_ISTILAH_COCOKKAN_SALDO.md`: sesuaikan contoh URL jika masih merepresentasikan implementasi aktif.
- `resources/content/tutorial.json`: ubah hanya bila ada URL `/admin` eksplisit yang dibaca pengguna. **Hasil verifikasi:** tidak ada URL `/admin` di file ini; seluruh 5 kemunculan kata "admin" adalah penyebutan peran (contoh: "hubungi admin via WhatsApp", "Setelah admin menyetujui order"), sehingga tutorial tidak perlu diubah untuk perubahan URL ini.

Tidak ada perubahan skema database, sehingga bagian Skema database tidak berubah.

## 4. Risiko dan Hal yang Perlu Diperhatikan

1. Nama route berubah dari `filament.admin.*` menjadi `filament.app.*`; seluruh `route('filament.admin...')`, `routeIs('filament.admin...')`, referensi di Blade, dan kunci preferensi widget harus diperbarui (daftar pada bagian 3.1). Referensi yang terlewat akan menyebabkan error `RouteNotFoundException` atau redirect ke halaman login yang salah.
2. Email, invoice, dan pesan Telegram yang sudah terkirim memuat `/admin/...`; redirect pada bagian 3.3 menangani ini.
3. Jalankan `php artisan route:clear` atau `php artisan optimize:clear` setelah deploy karena path panel, nama route, dan route berubah.
4. Spotlight Filament menghasilkan URL dari route panel sehingga idealnya mengikuti path baru otomatis; verifikasi manual.
5. Plugin impersonate dan developer logins mengarah ke dasbor panel; pastikan redirect mengikuti path baru.
6. Livewire endpoint tidak terpengaruh karena tidak berada di bawah path panel.
7. Data debugbar/telescope lama di `storage` memuat URL lama dan dibiarkan sesuai keputusan bagian 1.2.
8. Urutan route: redirect slug spesifik harus terdaftar sebelum redirect umum `/admin/{any}`.

## 5. Strategi Pengujian

Sesuai aturan `AGENTS.md`, jangan jalankan full suite di lokal; gunakan satu filter test per perintah dengan `--parallel`.

```powershell
php artisan test --filter="PasswordResetTest" --parallel
php artisan test --filter="SettingNavigationTest" --parallel
php artisan test --filter="ShareBukuResourceTest" --parallel
php artisan test --filter="UtangPiutangActionLabelTest" --parallel
php artisan test --filter="EmailVerifikasiTest" --parallel
php artisan test --filter="EmailNotifikasiPembayaranTest" --parallel
php artisan test --filter="TutorialTest" --parallel
php artisan test --filter="OnboardingTest" --parallel
php artisan test --filter="ManageVoucherCodesTest" --parallel
php artisan test --filter="BeliPaketTest" --parallel
php artisan test --filter="LanggananFeatureTest" --parallel
php artisan test --filter="VoucherLanggananTest" --parallel
php artisan test --filter="LandingPageTest" --parallel
php artisan test --filter="TrialLandingTest" --parallel
```

Selain test terfilter, verifikasi bahwa tidak ada referensi `filament.admin` tersisa:

```powershell
Get-ChildItem app,routes,resources,tests,config,lang -Recurse -Include *.php,*.json,*.blade.php |
  Select-String -Pattern 'filament\.admin'
```

Hasil yang diharapkan: kosong, kecuali dokumentasi historis di `docs/`.

Verifikasi manual tambahan:

- `php artisan route:list --path=app` menampilkan seluruh route panel di bawah `/app`.
- `php artisan route:list --path=admin` hanya menampilkan redirect kompatibilitas.
- Buka `/app`, `/app/login`, `/admin`, dan `/admin/transaksi` untuk memastikan redirect berjalan.
- Buka `/preview-email-verifikasi` di local untuk memastikan tautan email memakai `/app`.

Full test suite tetap dijalankan di GitHub Actions.

## 6. Ringkasan Urutan Pengerjaan

1. Ubah `->path('admin')` menjadi `->path('app')` **dan** `->id('admin')` menjadi `->id('app')` di `AdminPanelProvider.php`.
2. Perbarui seluruh referensi nama route `filament.admin.*` menjadi `filament.app.*` pada middleware onboarding, controller, `AppServiceProvider`, job Telegram, `welcome.blade.php`, dan test (bagian 3.1).
3. Perbarui seluruh URL literal `/admin` menjadi `/app` (bagian 3.2).
4. Perbarui redirect slug lama menjadi target `/app/...` dan tambahkan redirect permanen kompatibilitas `/admin` serta `/admin/{any}` ke `/app` (bagian 3.3).
5. Perbarui ekspektasi test lain yang memakai URL `/admin` (bagian 3.4).
6. Perbarui `FITUR_APLIKASI.md`, dokumen terkait, dan periksa `resources/content/tutorial.json` (bagian 3.5).
7. Bersihkan cache route/optimize, jalankan test terfilter, dan verifikasi `route:list` (bagian 5).
8. Cari ulang `filament.admin` dan `/admin` di `app`, `routes`, `resources`, `lang`, `config`, dan `tests`; sisa yang boleh ada hanya role pengguna `admin`, redirect kompatibilitas, dan kasus negatif yang memang disengaja.

