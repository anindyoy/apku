# TIA (Test Impact Analysis) Configuration

TIA menganalisis perubahan kode dan hanya menjalankan test yang terpengaruh, sehingga testing menjadi jauh lebih cepat.

## Ringkasan Konfigurasi

TIA sudah terintegrasi di Pest 5.0.4 dan tidak memerlukan plugin tambahan. Berikut adalah konfigurasi yang telah dilakukan:

### 1. File yang Dikonfigurasi

#### a. `.gitignore`
Ditambahkan `.pest-tia/` untuk mengabaikan direktori cache TIA.

#### b. `phpunit.xml`
TIA tidak memerlukan konfigurasi listener di `phpunit.xml` karena sudah terintegrasi langsung dengan Pest 5.0.4. Cukup pastikan logging configuration ada:
```xml
<logging>
    <log type="testdox-html" target="build/testdox.html"/>
</logging>
```

**⚠️ PENTING:** Jangan tambahkan `<listeners>` element di `phpunit.xml` karena akan menyebabkan validation error. TIA bekerja tanpa konfigurasi listener tambahan.

#### c. `.github/workflows/tests.yml`
Dibuat workflow GitHub Actions dengan TIA yang mencakup:
- Full git history fetch (penting untuk TIA)
- Cache TIA data antar runs
- Test execution dengan `--tia` flag
- Upload TIA cache sebagai artifact

## Cara Penggunaan

### Local Development

#### Basic TIA:
```bash
# Jalankan TIA (pertama kali akan menjalankan semua test)
vendor/bin/pest --tia

# Run berikutnya hanya menjalankan test yang terpengaruh
vendor/bin/pest --tia

# Force fresh run (abaikan cache)
vendor/bin/pest --tia --fresh

# Hanya tampilkan test file yang terpengaruh (tanpa menjalankan)
vendor/bin/pest --tia --filtered

# Clear cache TIA jika ada masalah
rm -rf .pest-tia
```

#### TIA dengan Coverage (Workflow yang Benar):
```bash
# STEP 1: Build TIA baseline (pertama kali atau setelah --fresh)
# Jalankan TIA TANPA coverage untuk build dependency graph
vendor/bin/pest --tia

# STEP 2: Setelah baseline terbentuk, jalankan dengan coverage
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --tia

# Atau dengan fresh rebuild jika baseline rusak
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --tia --fresh
```

**⚠️ PENTING - Workflow TIA + Coverage:**
1. **Pertama kali**: Jalankan `vendor/bin/pest --tia` (TANPA coverage) untuk build baseline
2. **Kedua kalinya**: Jalankan dengan coverage `$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --tia`
3. TIA akan menggunakan baseline yang sudah dibuat untuk menentukan test mana yang dijalankan
4. Coverage akan dihitung hanya untuk test yang dijalankan oleh TIA

**Alasan:**
- TIA perlu merekam dependency graph terlebih dahulu sebelum coverage diaktifkan
- Coverage memperluas edges yang bisa direkam, sehingga TIA perlu baseline terpisah
- Setelah baseline terbentuk, coverage runs akan reuse baseline tersebut

#### TIA dengan Parallel (TANPA Coverage):
```bash
# TIA + Parallel untuk kecepatan maksimal
$env:XDEBUG_MODE="off"; php artisan test --parallel --tia

# Dengan 4 processes
$env:XDEBUG_MODE="off"; php artisan test --parallel --processes=4 --tia
```

### CI/CD (GitHub Actions)

TIA akan otomatis berjalan saat:
- Push ke branch `main` atau `develop`
- Pull request ke branch `main` atau `develop`

Workflow akan:
1. Checkout dengan full git history (`fetch-depth: 0`)
2. Setup PHP 8.4 dengan extensions yang dibutuhkan
3. Install dependencies
4. Setup database MySQL
5. Run migrations
6. Restore TIA cache dari previous runs
7. Run tests dengan TIA
8. Upload TIA cache untuk digunakan di run berikutnya

## TIA Options

| Option | Deskripsi |
|--------|-----------|
| `--tia` | Enable TIA (default: enabled jika listener ada) |
| `--tia --fresh` | Discard recorded dependency graph dan record ulang |
| `--tia --filtered` | Hanya tampilkan test files yang terpengaruh (tanpa execute) |
| `--tia --locally` | Enable TIA hanya di local machine |
| `--tia --baselined` | Fetch shared dependency graph dari CI baseline |
| `--tia --refetch` | Force fresh fetch dari shared dependency graph |
| `--no-tia` | Disable TIA untuk run ini |

## TIA Storage Directory

Untuk melihat lokasi storage TIA:
```bash
vendor/bin/pest --baseline
```

Default location: `C:\Users\ACER\.pest\tia\apku-07daa8a98201950f`

## Catatan Penting

1. **Git History**: TIA memerlukan akses ke git history untuk menganalisis perubahan. Di CI, pastikan `fetch-depth: 0`.

2. **First Run**: Run pertama akan lebih lama karena TIA perlu membangun dependency graph dari semua test.

3. **Cache**: TIA menyimpan cache di `.pest-tia/` (local) dan di CI menggunakan GitHub Actions cache.

4. **Coverage**: TIA bekerja dengan baik bersama code coverage. Pastikan `<source>` di `phpunit.xml` sudah dikonfigurasi dengan benar.

5. **Fresh Start**: Jika TIA memberikan hasil yang tidak diharapkan, gunakan `--tia --fresh` untuk rebuild dependency graph.

6. **Xdebug/PCOV Required**: TIA memerlukan **Xdebug** atau **PCOV** extension untuk bekerja. Tanpa extension ini, TIA akan diskipped dengan pesan "TIA is skipped as it needs ext-pcov or Xdebug".

   **Install Xdebug:**
   ```bash
   # Windows dengan PECL
   pecl install xdebug

   # Atau dengan Composer (disarankan)
   composer require --dev xdebug/xdebug

   # Enable di php.ini
   zend_extension=xdebug
   xdebug.mode=coverage
   ```

   **Install PCOV (alternatif lebih ringan):**
   ```bash
   # Windows dengan PECL
   pecl install pcov

   # Enable di php.ini
   extension=pcov
   pcov.enabled=1
   ```

## Troubleshooting

### TIA tidak bekerja di local
```bash
# Pastikan listener ada di phpunit.xml
# Clear cache dan run fresh
rm -rf .pest-tia
vendor/bin/pest --tia --fresh
```

### TIA tidak bekerja di CI
- Pastikan `fetch-depth: 0` di checkout step
- Pastikan cache TIA di-restore dengan benar
- Periksa apakah artifact TIA cache di-upload dengan benar

### Semua test dijalankan meskipun ada cache
```bash
# Cek apakah ada perubahan yang tidak terdeteksi
vendor/bin/pest --tia --filtered

# Rebuild dependency graph
vendor/bin/pest --tia --fresh
```

### Error: "One or more workers failed to generate coverage files"
Error ini terjadi ketika menjalankan TIA + Coverage + Parallel. **Kombinasi ini tidak didukung** karena TIA mengubah cara test dijalankan yang tidak kompatibel dengan parallel coverage collection.

**❌ JANGAN gunakan: TIA + Coverage + Parallel together**
```bash
# INI AKAN ERROR
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --parallel --tia
```

**✅ Solusi yang Benar:**

**Opsi 1: TIA + Coverage (tanpa parallel) - untuk coverage yang akurat**
```bash
# Cara paling stabil untuk coverage
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --tia

# Atau dengan fresh rebuild
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --tia --fresh
```

**Opsi 2: TIA + Parallel (tanpa coverage) - untuk kecepatan maksimal**
```bash
# Cepat tapi tanpa coverage
$env:XDEBUG_MODE="off"; php artisan test --parallel --tia

# Dengan 4 processes
$env:XDEBUG_MODE="off"; php artisan test --parallel --processes=4 --tia
```

**Opsi 3: Jika perlu coverage + parallel, JALANAN TERPISAH:**
```bash
# Step 1: Jalankan TIA untuk identify test yang dijalankan
$env:XDEBUG_MODE="off"; php artisan test --tia --filtered > affected-tests.txt

# Step 2: Jalankan coverage untuk test yang terpengaruh saja (manual)
$env:XDEBUG_MODE="off"; php artisan test --coverage-html "tests/coverage/" --filter=<affected-test-file>
```

**Rekomendasi:**
- Untuk **coverage yang akurat**: Gunakan **TIA + Coverage** (tanpa parallel)
- Untuk **kecepatan testing**: Gunakan **TIA + Parallel** (tanpa coverage)
- **Jangan pernah** menggabungkan ketiganya: TIA + Coverage + Parallel

## Referensi

- [Pest TIA Documentation](https://pestphp.com/docs/tia)
- [Pest 5.0 Release Notes](https://pestphp.com/docs/releases/5.0)