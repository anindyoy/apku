# Plan: Jalankan Coverage Test (Parallel) & Langkah Peningkatan Coverage

## Ringkasan Proyek

- **Framework:** Laravel 13 + Filament 5 + Pest PHP 5
- **Database:** MariaDB (port 3308, db: `apku_testing`)
- **Coverage Saat Ini:** ~45.93% lines, ~19.51% classes (dari laporan sebelumnya)
- **Total Test:** 81 test cases across Feature tests

---

## Langkah 1: Jalankan Coverage Test dengan Parallel

### Perintah Utama

```bash
# Pastikan database testing sudah ada
php database/create_testing_db.sql (via MySQL client)

# Jalankan coverage test dengan parallel
php artisan test --parallel --coverage --min=0 2>&1 | Tee-Object -FilePath test-output.txt
```

### Alternatif (jika `--coverage` tidak didukung di parallel mode):

```bash
# Jalankan coverage tanpa parallel dulu untuk mendapatkan report
./vendor/bin/pest --coverage --coverage-html=tests/coverage-new 2>&1 | Tee-Object -FilePath test-output.txt
```

### Output yang Dihasilkan:
- **HTML Report:** `tests/coverage-new/index.html`
- **Terminal output:** Summary coverage metrics per class/file

---

## Langkah 2: Analisis Coverage Report

### 2.1 Buka HTML Coverage Report
Buka `tests/coverage-new/index.html` di browser untuk melihat detail:
- Line coverage per file
- Method coverage per class
- Branch coverage (jika tersedia)

### 2.2 Identifikasi File dengan Coverage 0%
Fokus pada file yang belum ada test sama sekali:
- `app/Filament/Resources/*/Pages/*` (Create, Edit, List pages)
- `app/Models/*` (methods, relationships, scopes)
- `app/Observers/*` (TransaksiObserver, UtangPiutangObserver)
- `app/Policies/UserPolicy.php`

---

## Langkah 3: Langkah Paling Penting untuk Meningkatkan Coverage

### Priority 1: Filament Resources — Pages & Widgets (Dampak Tertinggi)

**Target:** Naikkan dari 37.47% ke 70%+

**File yang perlu diuji:**
| Resource | Pages | Status |
|----------|-------|--------|
| `BukuKasResource` | CreateBukuKas, EditBukuKas, ListBukuKas | Sudah ada test |
| `TransaksiResource` | CreateTransaksi, EditTransaksi, ListTransaksis | Partial test |
| `PiutangResource` | CreatePiutang, EditPiutang, ListPiutangs, PiutangDetail | Sudah ada test |
| `UtangResource` | CreateUtang, EditUtang, ListUtangs, UtangDetail | Sudah ada test |
| `ShareBukuResource` | CreateShareBuku, EditShareBuku, ListShareBukus | Sudah ada test |
| `UserResource` | CreateUser, EditUser, ListUsers | Partial test |

**Widget yang perlu diuji:**
- `KasOverview` — widget di TransaksiResource
- `UtangOverview` — widget di UtangResource
- `PiutangOverview` — widget di PiutangResource
- `UtangPiutangDetailOverview` — widget global

**Test yang harus ditambahkan:**
1. Test `CreateTransaksi` page — belum ada test create transaksi
2. Test validation rules di semua Create/Edit pages
3. Test widget rendering di halaman list

---

### Priority 2: Models — Relationships, Accessors, Scopes (Dampak Tinggi)

**Target:** Naikkan dari 57.96% ke 80%+

**Model yang belum tercover:**
| Model | Coverage | Yang Perlu Diuji |
|-------|----------|------------------|
| `Transaksi` | Partial | `form()`, relationships |
| `BukuKas` | Partial | relationships, saldo computation |
| `JenisTransaksi` | Rendah | factory, attributes |
| `ShareBuku` | Rendah | relationships |
| `UtangPiutang` | Rendah | `stat()`, relationships |
| `UtangPiutangDetail` | Rendah | scopes (`tambah()`, `kurang()`) |
| `Wilayah` | Rendah | relationships |
| `User` | Rendah | `isSuper()`, `notSuper()` scopes |
| `Scopes/UserScope` | 0% | Scope query behavior |

**Test yang harus ditambahkan:**
1. Unit test untuk setiap model relationship
2. Test `Transaksi::form()` — form schema generation
3. Test `UserScope` — pastikan query hanya return data user yang benar
4. Test `UtangPiutangDetail` scopes: `tambah()`, `kurang()`

---

### Priority 3: Observers (Mudah, Dampak Kecil)

**Target:** Naikkan dari 40.90% ke 100%

**File:**
- `app/Observers/TransaksiObserver.php` — 3 methods aktif (`created`, `updated`, `deleted`)
- `app/Observers/UtangPiutangObserver.php` — 1 method aktif (`deleted`)

**Test yang harus ditambahkan:**
1. Test `TransaksiObserver::created()` — verify saldo berubah saat transaksi dibuat
2. Test `TransaksiObserver::updated()` — verify saldo berubah saat nominal diupdate
3. Test `UtangPiutangObserver::deleted()` — verify cascade delete detail

---

### Priority 4: Policies (Mudah, Dampak Kecil)

**Target:** Naikkan dari 42.85% ke 100%

**File:** `app/Policies/UserPolicy.php` — 7 methods

**Test yang harus ditambahkan:**
1. Test `viewAny()` — super user bisa, reguler tidak bisa
2. Test `view()`, `create()`, `update()`, `delete()`, `restore()`, `forceDelete()`
3. Test dengan user yang tidak login (unauthenticated)

---

### Priority 5: Filament Pages Custom

**File:**
- `app/Filament/Pages/AkunSaya.php` — sudah ada test
- `app/Filament/Pages/Kategori.php` — sudah ada test

**Test yang harus ditambahkan:**
1. Test edge cases untuk `AkunSaya` — update profil dengan data invalid
2. Test `Kategori` — CRUD kategori pemasukan dan pengeluaran

---

## Rencana Eksekusi Todo List

1. [ ] Jalankan coverage test dengan parallel
2. [ ] Analisis hasil coverage report
3. [ ] Buat test untuk `CreateTransaksi` page
4. [ ] Buat test untuk Filament Widgets (KasOverview, UtangOverview, PiutangOverview)
5. [ ] Buat Unit test untuk Model relationships & scopes
6. [ ] Buat test untuk TransaksiObserver
7. [ ] Buat test untuk UtangPiutangObserver
8. [ ] Buat test untuk UserPolicy
9. [ ] Tambahkan edge case tests untuk existing test files
10. [ ] Jalankan ulang coverage test untuk verifikasi peningkatan

---

## Diagram Prioritas

```
Filament Resources  →████████████████████  (569 lines uncovered)
Models              →████████████          (132 lines uncovered)
Observers           →███                   (13 lines uncovered)
Policies            →█                     (4 lines uncovered)
```

---

## Catatan Teknis

- **Parallel testing:** Gunakan `php artisan test --parallel` untuk menjalankan tests di multiple processes
- **Coverage:** Pest/PHPUnit memerlukan `pcov` atau `xdebug` extension untuk menghitung coverage
- **Database:** Pastikan `apku_testing` database sudah dibuat sebelum menjalankan test
- **Minimum threshold:** Set `--min=45` untuk memastikan coverage tidak turun
