# Standar Placeholder TextInput APKu

Dokumen ini mendefinisikan standar dan aturan untuk menambahkan placeholder pada komponen `TextInput` Filament di seluruh aplikasi APKu.

## 📋 Aturan Umum

1. **WAJIB** menambahkan `->placeholder(...)` pada setiap `TextInput` yang:
   - Bisa diisi user (tidak disabled/readonly)
   - Tidak memiliki nilai default yang sudah terisi
   - Bukan field password yang sudah di-handle khusus

2. **TIDAK PERLU** placeholder pada field:
   - `->disabled()` atau `->readonly()`
   - Sudah terisi nilai default dari database
   - Field hidden/internal

3. Placeholder harus **singkat, jelas, dan memberikan contoh nyata** format yang diharapkan.

---

## 🎯 Pola Placeholder per Tipe Field

| Tipe Field | Pola Placeholder | Contoh |
|------------|------------------|--------|
| **Nama/Label/Identitas** | `Contoh: [contoh 1], [contoh 2]` | `Contoh: Kas Pribadi, Kas Rumah Tangga` |
| **Nominal/Uang (Rp)** | `Contoh: [angka tanpa titik/koma]` | `Contoh: 500000` |
| **Berat/Decimal (komma)** | `Contoh: [angka dengan koma]` | `Contoh: 0,5 atau 1` |
| **Email** | `contoh@email.com` | `contoh@email.com` |
| **Nomor HP** | `Contoh: 08xx-xxxx-xxxx` | `Contoh: 0812-3456-7890` |
| **URL** | `https://domain.com/path` | `https://api.example.com/harga-emas` |
| **Deskripsi/Catatan** | `Catatan singkat (opsional)` | `Catatan transaksi (opsional)` |
| **Password** | `Minimal [n] karakter` | `Minimal 8 karakter` |
| **Kode/Token** | `Contoh: [format kode]` | `Contoh: PROMO2024, DISKON50` |
| **Tipe/Enum** | `pilihan1 / pilihan2 / pilihan3` | `regular / premium / admin` |
| **Durasi (hari/bulan)** | `Contoh: [angka]` | `Contoh: 30` |
| **Urutan/Index** | `Contoh: 1, 2, 3...` | `Contoh: 1, 2, 3...` |

---

## 📝 Contoh Implementasi

### TextInput Biasa
```php
TextInput::make('nama_buku')
    ->required()
    ->maxLength(50)
    ->placeholder('Contoh: Kas Pribadi, Kas Rumah Tangga, Kas Usaha'),
```

### TextInput Nominal (prefix Rp)
```php
TextInput::make('saldo')
    ->prefix('Rp')
    ->numeric()
    ->placeholder('Contoh: 500000'),
```

### TextInput Email
```php
TextInput::make('email')
    ->email()
    ->placeholder('contoh@email.com'),
```

### TextInput Password
```php
TextInput::make('password')
    ->password()
    ->placeholder('Minimal 8 karakter'),
```

### TextInput Deskripsi (opsional, maxLength)
```php
TextInput::make('description')
    ->maxLength(200)
    ->placeholder('Catatan singkat (maks. 200 karakter)'),
```

---

## ✅ Checklist Saat Membuat/Modifikasi Form

- [ ] Semua `TextInput` editable memiliki `->placeholder(...)`
- [ ] Placeholder mengikuti pola di tabel di atas
- [ ] Field `disabled()/readonly()` TIDAK memiliki placeholder
- [ ] Placeholder tidak terlalu panjang (maks ~50 karakter)
- [ ] Menggunakan Bahasa Indonesia
- [ ] Contoh nilai realistis untuk konteks aplikasi keuangan

---

## 🔧 Helper Function (Opsional)

Jika ingin konsistensi lebih ketat, bisa buat helper trait:

```php
// app/Filament/Concerns/HasPlaceholderStandards.php
trait HasPlaceholderStandards
{
    protected function placeholder(string $type, array $examples = []): string
    {
        return match ($type) {
            'name' => 'Contoh: ' . implode(', ', $examples ?: ['Nama 1', 'Nama 2']),
            'money' => 'Contoh: ' . ($examples[0] ?? '500000'),
            'weight' => 'Contoh: ' . ($examples[0] ?? '0,5'),
            'email' => 'contoh@email.com',
            'phone' => 'Contoh: 0812-3456-7890',
            'url' => 'https://domain.com/path',
            'description' => 'Catatan singkat (opsional)',
            'password' => 'Minimal 8 karakter',
            'code' => 'Contoh: ' . implode(', ', $examples ?: ['KODE1', 'KODE2']),
            'type' => implode(' / ', $examples ?: ['tipe1', 'tipe2']),
            'duration' => 'Contoh: ' . ($examples[0] ?? '30'),
            'order' => 'Contoh: 1, 2, 3...',
            default => '',
        };
    }
}
```

Penggunaan:
```php
use HasPlaceholderStandards;

// ...
->placeholder($this->placeholder('money'))
->placeholder($this->placeholder('name', ['Gaji', 'Bonus', 'Penjualan']))
```

---

## 📌 Catatan Penting

- Placeholder **bukan** label — gunakan `->label()` untuk label
- Placeholder **bukan** validation message — gunakan `->validationMessages()`
- Placeholder harus konsisten dengan `helperText()` jika keduanya ada
- Saat menambah field baru di migration/model, pastikan form-nya sudah punya placeholder
- Update dokumen ini jika pola baru ditemukan

---

*Dibuat: 2026-10-08 | Terakhir diperbarui: 2026-10-08*