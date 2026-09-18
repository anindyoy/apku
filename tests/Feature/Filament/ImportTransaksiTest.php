<?php

use App\Filament\Resources\ImportTransaksiResource\Pages\ListImportTransaksis;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Jobs\ProsesImportTransaksi;
use App\Models\Dompet;
use App\Models\ImportTransaksi;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Services\ImportTransaksiService;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

function siapkanDataImportTransaksi(): array
{
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $bukuKas->update(['nama_buku' => 'Kas Utama', 'saldo' => 0, 'is_default' => true]);
    $dompet = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Cash',
        'saldo' => 0,
        'is_default' => true,
    ]);
    $pemasukan = JenisTransaksi::create([
        'user_id' => $user->id,
        'nama_jenis' => 'Gaji',
        'tipe' => 'Pemasukan',
    ]);
    $pengeluaran = JenisTransaksi::create([
        'user_id' => $user->id,
        'nama_jenis' => 'Makanan',
        'tipe' => 'Pengeluaran',
    ]);

    return compact('user', 'bukuKas', 'dompet', 'pemasukan', 'pengeluaran');
}

function fileCsvImport(string $isi): UploadedFile
{
    return UploadedFile::fake()->createWithContent('transaksi.csv', $isi);
}

test('import transaksi csv menyimpan semua baris dan memperbarui saldo', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,5000000,Gaji bulanan',
        now()->subHours(2)->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Makanan,35000,Makan siang',
    ]));

    $hasil = app(ImportTransaksiService::class)->impor($data['user'], $file);

    expect($hasil['jumlah_baris'])->toBe(2)
        ->and($hasil['total_pemasukan'])->toBe(5000000)
        ->and($hasil['total_pengeluaran'])->toBe(35000)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(2)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(4965000)
        ->and($data['dompet']->fresh()->saldo)->toBe(4965000)
        ->and(ImportTransaksi::query()->where('user_id', $data['user']->id)->count())->toBe(1)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->whereNotNull('import_transaksi_id')->count())->toBe(2);
})->group('filament', 'import-transaksi');

test('import riwayat tanpa dampak saldo tetap dapat diubah dan dibatalkan', function () {
    $data = siapkanDataImportTransaksi();
    $data['bukuKas']->update(['saldo' => 750000]);
    $data['dompet']->update(['saldo' => 750000]);
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,100000,Riwayat lama',
    ]));

    app(ImportTransaksiService::class)->impor($data['user'], $file, pengaruhiSaldo: false);
    $batch = ImportTransaksi::query()->where('user_id', $data['user']->id)->firstOrFail();
    $transaksi = $batch->transaksi()->firstOrFail();

    expect($batch->pengaruhi_saldo)->toBeFalse()
        ->and($transaksi->pengaruhi_saldo)->toBeFalse()
        ->and($data['bukuKas']->fresh()->saldo)->toBe(750000)
        ->and($data['dompet']->fresh()->saldo)->toBe(750000);

    app(\App\Services\TransaksiService::class)->ubah($data['user'], $transaksi, ['nominal' => 200000]);
    expect($data['bukuKas']->fresh()->saldo)->toBe(750000)
        ->and($data['dompet']->fresh()->saldo)->toBe(750000);

    app(ImportTransaksiService::class)->batalkan($data['user'], $batch);
    expect($data['bukuKas']->fresh()->saldo)->toBe(750000)
        ->and($data['dompet']->fresh()->saldo)->toBe(750000);
})->group('filament', 'import-transaksi', 'saldo-import');

test('baris import dengan kas atau dompet kosong memakai nilai default', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,,,Gaji,100000,Keduanya kosong',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,,Gaji,20000,Dompet kosong',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,,Cash,Gaji,30000,Kas kosong',
    ]));
    $service = app(ImportTransaksiService::class);

    expect($service->pratinjau($data['user'], $file)['errors'])->toBe([]);

    $hasil = $service->impor($data['user'], $file);

    expect($hasil['jumlah_baris'])->toBe(3)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)
            ->where('buku_kas_id', $data['bukuKas']->id)
            ->where('dompet_id', $data['dompet']->id)->count())->toBe(3)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(150000)
        ->and($data['dompet']->fresh()->saldo)->toBe(150000);
})->group('filament', 'import-transaksi');

test('import menerima tanggal tanpa waktu dan tetap menjaga waktu yang diisi', function () {
    $data = siapkanDataImportTransaksi();
    $tanggal = now()->subDay()->format('Y-m-d');
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        $tanggal.',Pemasukan,Kas Utama,Cash,Gaji,100000,Tanggal saja',
        $tanggal.' 14:30,Pemasukan,Kas Utama,Cash,Gaji,20000,Dengan waktu',
    ]));
    $service = app(ImportTransaksiService::class);

    expect($service->pratinjau($data['user'], $file)['errors'])->toBe([]);

    $service->impor($data['user'], $file);

    expect(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)
        ->where('deskripsi', 'Tanggal saja')->firstOrFail()->tanggal)->toBe($tanggal.' 00:00:00')
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)
            ->where('deskripsi', 'Dengan waktu')->firstOrFail()->tanggal)->toBe($tanggal.' 14:30:00');
})->group('filament', 'import-transaksi');

test('file besar disimpan privat dan diproses melalui antrean tanpa transaksi ganda', function () {
    Queue::fake();
    Storage::fake('local');
    $data = siapkanDataImportTransaksi();
    $baris = ['tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi'];

    foreach (range(1, ImportTransaksiService::BATAS_BARIS_LANGSUNG + 1) as $nomor) {
        $baris[] = now()->subDay()->format('Y-m-d H:i').",Pemasukan,Kas Utama,Cash,Gaji,1,Baris {$nomor}";
    }

    $service = app(ImportTransaksiService::class);
    $batch = $service->antrekan($data['user'], fileCsvImport(implode("\n", $baris)), pengaruhiSaldo: false);
    $pathFile = $batch->path_file;

    Queue::assertPushedOn('import-transaksi', ProsesImportTransaksi::class);
    Storage::disk('local')->assertExists($batch->path_file);
    expect($batch->status)->toBe('menunggu')
        ->and($batch->jumlah_baris)->toBe(1001)
        ->and($batch->pengaruhi_saldo)->toBeFalse()
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(0);

    (new ProsesImportTransaksi($batch->id))->handle($service);

    $batch->refresh();
    expect($batch->status)->toBe('berhasil')
        ->and($batch->jumlah_diproses)->toBe(1001)
        ->and($batch->path_file)->toBeNull()
        ->and($batch->selesai_diproses_at)->not->toBeNull()
        ->and(Transaksi::withoutGlobalScopes()->where('import_transaksi_id', $batch->id)->count())->toBe(1001)
        ->and(Transaksi::withoutGlobalScopes()->where('import_transaksi_id', $batch->id)->where('pengaruhi_saldo', false)->count())->toBe(1001)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0);
    Storage::disk('local')->assertMissing($pathFile);
})->group('filament', 'import-transaksi', 'antrean-import');

test('file besar yang sama tidak dapat masuk antrean lebih dari sekali', function () {
    Queue::fake();
    Storage::fake('local');
    $data = siapkanDataImportTransaksi();
    $baris = ['tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi'];

    foreach (range(1, ImportTransaksiService::BATAS_BARIS_LANGSUNG + 1) as $nomor) {
        $baris[] = now()->subDay()->format('Y-m-d H:i').",Pemasukan,Kas Utama,Cash,Gaji,1,Baris {$nomor}";
    }

    $isi = implode("\n", $baris);
    $service = app(ImportTransaksiService::class);
    $service->antrekan($data['user'], fileCsvImport($isi));

    expect(fn () => $service->antrekan($data['user'], fileCsvImport($isi)))
        ->toThrow(ValidationException::class)
        ->and(ImportTransaksi::query()->where('user_id', $data['user']->id)->count())->toBe(1);
    Queue::assertPushed(ProsesImportTransaksi::class, 1);
})->group('filament', 'import-transaksi', 'antrean-import');

test('header umum dipetakan otomatis dan dapat langsung diimpor', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'Date,Type,Account,Wallet,Category,Amount,Memo',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,275000,Kolom bahasa Inggris',
    ]));
    $service = app(ImportTransaksiService::class);
    $header = $service->bacaHeader($file);
    $pemetaan = $service->sarankanPemetaan($header);

    $hasil = $service->impor($data['user'], $file, pemetaan: $pemetaan);

    expect($pemetaan)->toBe([
        'tanggal' => 'date',
        'jenis' => 'type',
        'buku_kas' => 'account',
        'dompet' => 'wallet',
        'kategori' => 'category',
        'nominal' => 'amount',
        'deskripsi' => 'memo',
    ])->and($hasil['jumlah_baris'])->toBe(1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(275000)
        ->and($data['dompet']->fresh()->saldo)->toBe(275000);
})->group('filament', 'import-transaksi', 'pemetaan-import');

test('pengguna dapat memetakan nama kolom yang tidak dikenali', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'Waktu Catat,Arah Dana,Nama Kas,Sumber Dana,Kelompok,Nilai Rupiah,Catatan Saya',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Makanan,45000,Makan bersama',
    ]));
    $pemetaan = [
        'tanggal' => 'waktu_catat',
        'jenis' => 'arah_dana',
        'buku_kas' => 'nama_kas',
        'dompet' => 'sumber_dana',
        'kategori' => 'kelompok',
        'nominal' => 'nilai_rupiah',
        'deskripsi' => 'catatan_saya',
    ];

    $hasil = app(ImportTransaksiService::class)->impor($data['user'], $file, pemetaan: $pemetaan);

    expect($hasil['jumlah_baris'])->toBe(1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(-45000)
        ->and(Transaksi::withoutGlobalScopes()->where('deskripsi', 'Makan bersama')->exists())->toBeTrue();
})->group('filament', 'import-transaksi', 'pemetaan-import');

test('pemetaan menolak kolom wajib kosong dan sumber duplikat', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'Date,Type,Account,Wallet,Category,Amount',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,100000',
    ]));

    expect(fn () => app(ImportTransaksiService::class)->pratinjau($data['user'], $file, pemetaan: [
        'tanggal' => 'date',
        'jenis' => 'type',
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(ImportTransaksiService::class)->pratinjau($data['user'], $file, pemetaan: [
        'tanggal' => 'date',
        'jenis' => 'type',
        'buku_kas' => 'account',
        'dompet' => 'wallet',
        'kategori' => 'category',
        'nominal' => 'category',
    ]))->toThrow(ValidationException::class);
})->group('filament', 'import-transaksi', 'pemetaan-import');

test('kategori baru hanya dibuat setelah dikonfirmasi bersama import', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Bonus Proyek,325000,Bonus proyek baru',
    ]));
    $service = app(ImportTransaksiService::class);
    $tanpaKonfirmasi = $service->pratinjau($data['user'], $file);
    $denganKonfirmasi = $service->pratinjau($data['user'], $file, buatKategoriOtomatis: true);

    expect($tanpaKonfirmasi['errors'])->not->toBeEmpty()
        ->and($denganKonfirmasi['errors'])->toBe([])
        ->and(array_values($denganKonfirmasi['kategori_baru']['Pemasukan']))->toBe(['Bonus Proyek']);
    $this->assertDatabaseMissing('jenis_transaksi', [
        'user_id' => $data['user']->id,
        'nama_jenis' => 'Bonus Proyek',
    ]);

    $hasil = $service->impor($data['user'], $file, buatKategoriOtomatis: true);

    $this->assertDatabaseHas('jenis_transaksi', [
        'user_id' => $data['user']->id,
        'nama_jenis' => 'Bonus Proyek',
        'tipe' => 'Pemasukan',
    ]);
    expect($hasil['jumlah_baris'])->toBe(1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(325000)
        ->and(Transaksi::withoutGlobalScopes()->whereHas('jenis_transaksi', fn ($query) => $query->where('nama_jenis', 'Bonus Proyek'))->exists())->toBeTrue();
})->group('filament', 'import-transaksi', 'kategori-otomatis-import');

test('kategori baru tidak dibuat ketika batch memiliki baris tidak valid', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Keperluan Baru,50000,Baris valid',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Pemasukan Baru,0,Baris tidak valid',
    ]));

    expect(fn () => app(ImportTransaksiService::class)->impor($data['user'], $file, buatKategoriOtomatis: true))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('jenis_transaksi', [
        'user_id' => $data['user']->id,
        'nama_jenis' => 'Keperluan Baru',
    ]);
    $this->assertDatabaseMissing('jenis_transaksi', [
        'user_id' => $data['user']->id,
        'nama_jenis' => 'Pemasukan Baru',
    ]);
    expect(ImportTransaksi::query()->where('user_id', $data['user']->id)->count())->toBe(0)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0);
})->group('filament', 'import-transaksi', 'kategori-otomatis-import');

test('pratinjau xlsx tidak mengubah transaksi atau saldo', function () {
    $data = siapkanDataImportTransaksi();
    $path = tempnam(sys_get_temp_dir(), 'import-transaksi-test-').'.xlsx';
    app(ImportTransaksiService::class)->buatTemplateXlsx($path);

    try {
        $reader = new XlsxReader;
        $reader->open($path);
        $contohTanggal = null;
        $nomorBaris = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $nomorBaris++;

                if ($nomorBaris === 2) {
                    $contohTanggal = $row->getCells()[0]->getValue();

                    break;
                }
            }

            break;
        }

        $reader->close();
        $hasil = app(ImportTransaksiService::class)->pratinjau($data['user'], $path);
    } finally {
        @unlink($path);
    }

    expect($contohTanggal)->toBe(now()->format('Y-m-d'))
        ->and($hasil['jumlah_baris'])->toBe(1)
        ->and($hasil['errors'])->toBe([])
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(0)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0);
})->group('filament', 'import-transaksi');

test('pratinjau import menjelaskan format tanggal yang salah dan tanggal masa depan', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        '15/09/2026 14:30,Pemasukan,Kas Utama,Cash,Gaji,100000,Format salah',
        '2026/09/15,Pemasukan,Kas Utama,Cash,Gaji,100000,Format salah lagi',
        now()->addDay()->format('Y-m-d').',Pemasukan,Kas Utama,Cash,Gaji,100000,Masa depan',
    ]));

    $hasil = app(ImportTransaksiService::class)->pratinjau($data['user'], $file);
    $html = Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->instance()
        ->formatPratinjauImport($hasil)
        ->toHtml();

    expect($hasil['errors'])->toContain('Baris 2, kolom tanggal: format tanggal tidak valid.')
        ->toContain('Baris 3, kolom tanggal: format tanggal tidak valid.')
        ->toContain('Baris 4, kolom tanggal: tanggal tidak boleh berada di masa depan.')
        ->and(substr_count($html, 'Format tanggal yang benar:'))->toBe(1)
        ->and(substr_count($html, 'contoh: 2026-09-15'))->toBe(2)
        ->and(strpos($html, 'Format tanggal yang benar:'))->toBeLessThan(strpos($html, 'Baris 2, kolom tanggal'));
})->group('filament', 'import-transaksi');

test('baris import yang tidak valid membatalkan seluruh batch', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,100000,Valid',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Gaji,25000,Kategori salah',
    ]));

    expect(fn () => app(ImportTransaksiService::class)->impor($data['user'], $file))
        ->toThrow(ValidationException::class);

    expect(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(0)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0)
        ->and(ImportTransaksi::query()->where('user_id', $data['user']->id)->count())->toBe(0);
})->group('filament', 'import-transaksi');

test('laporan error xlsx memuat data asli dan alasan kegagalan', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Gaji,-25000,Kategori dan nominal salah',
    ]));
    $path = tempnam(sys_get_temp_dir(), 'laporan-error-import-test-');

    try {
        $jumlahError = app(ImportTransaksiService::class)->buatLaporanErrorXlsx($data['user'], $file, $path);
        $reader = new XlsxReader;
        $reader->open($path);
        $baris = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $baris[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
            }

            break;
        }

        $reader->close();
    } finally {
        @unlink($path);
    }

    expect($jumlahError)->toBe(1)
        ->and($baris[0])->toBe([
            'baris', 'tanggal', 'jenis', 'buku_kas', 'dompet', 'kategori', 'nominal', 'deskripsi', 'kesalahan',
        ])
        ->and($baris[1][0])->toBe(2)
        ->and($baris[1][2])->toBe('Pengeluaran')
        ->and($baris[1][5])->toBe('Gaji')
        ->and($baris[1][6])->toBe('-25000')
        ->and($baris[1][8])->toContain('kolom nominal')
        ->and($baris[1][8])->toContain('kolom aktivitas');
})->group('filament', 'import-transaksi', 'laporan-error-import');

test('file yang sama tidak dapat diimpor dua kali', function () {
    $data = siapkanDataImportTransaksi();
    $isi = implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,100000,Import tunggal',
    ]);

    app(ImportTransaksiService::class)->impor($data['user'], fileCsvImport($isi));

    expect(fn () => app(ImportTransaksiService::class)->impor($data['user'], fileCsvImport($isi)))
        ->toThrow(ValidationException::class)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(100000);
})->group('filament', 'import-transaksi');

test('halaman transaksi menyediakan aksi import dan riwayat import', function () {
    $data = siapkanDataImportTransaksi();

    Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertActionExists('Import Transaksi')
        ->assertActionExists('Riwayat Import');
})->group('filament', 'import-transaksi');

test('modal import menampilkan pemetaan dalam tiga kolom pada layar lebar', function () {
    $data = siapkanDataImportTransaksi();

    $component = Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->instance();
    $action = $component->getAction('Import Transaksi');
    $grid = collect($action->getSchema(Schema::make($component))->getComponents())
        ->first(fn ($item): bool => $item instanceof Grid);

    expect($grid)->not->toBeNull()
        ->and($grid->getColumns())->toMatchArray(['default' => 1, 'md' => 2, 'xl' => 3])
        ->and((string) $action->getModalWidth())->toBe('5xl');
})->group('filament', 'import-transaksi');

test('modal import menempatkan dua pilihan dalam grid dua kolom', function () {
    $data = siapkanDataImportTransaksi();
    $component = Livewire::actingAs($data['user'])->test(ListTransaksis::class)->instance();
    $action = $component->getAction('Import Transaksi');
    $grid = collect($action->getSchema(Schema::make($component))->getComponents())
        ->filter(fn ($item): bool => $item instanceof Grid)
        ->last();

    expect($grid)->not->toBeNull()
        ->and($grid->getColumns())->toMatchArray(['default' => 1, 'md' => 2])
        ->and(collect($grid->getChildSchema()->getComponents())->map(fn ($item): string => $item->getName())->all())
        ->toBe(['pengaruhi_saldo', 'buat_kategori_otomatis']);
})->group('filament', 'import-transaksi');

test('modal import menyediakan unduhan contoh template', function () {
    $data = siapkanDataImportTransaksi();

    expect(view('filament.resources.transaksi-resource.pages.import-template')->render())
        ->toContain('Unduh contoh template XLSX', 'wire:click="unduhTemplateImport"', 'text-gray-800 dark:text-gray-800');

    Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->call('unduhTemplateImport')
        ->assertFileDownloaded('template-import-transaksi.xlsx');
})->group('filament', 'import-transaksi');

test('pembatalan batch menghapus transaksi dan memulihkan seluruh saldo', function () {
    $data = siapkanDataImportTransaksi();
    $file = fileCsvImport(implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,250000,Pemasukan batch',
        now()->subHours(2)->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Makanan,50000,Pengeluaran batch',
    ]));

    app(ImportTransaksiService::class)->impor($data['user'], $file);
    $batch = ImportTransaksi::query()->where('user_id', $data['user']->id)->firstOrFail();

    app(ImportTransaksiService::class)->batalkan($data['user'], $batch);

    expect(Transaksi::withoutGlobalScopes()->where('import_transaksi_id', $batch->id)->count())->toBe(0)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0)
        ->and($batch->fresh()->status)->toBe('dibatalkan')
        ->and($batch->fresh()->dibatalkan_at)->not->toBeNull();
})->group('filament', 'import-transaksi', 'pembatalan-import');

test('file yang dibatalkan dapat diimpor ulang tanpa membuat batch baru', function () {
    $data = siapkanDataImportTransaksi();
    $isi = implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,Gaji,100000,Import ulang',
    ]);

    app(ImportTransaksiService::class)->impor($data['user'], fileCsvImport($isi));
    $batch = ImportTransaksi::query()->where('user_id', $data['user']->id)->firstOrFail();
    app(ImportTransaksiService::class)->batalkan($data['user'], $batch);
    app(ImportTransaksiService::class)->impor($data['user'], fileCsvImport($isi));

    expect(ImportTransaksi::query()->where('user_id', $data['user']->id)->count())->toBe(1)
        ->and($batch->fresh()->status)->toBe('berhasil')
        ->and($batch->fresh()->dibatalkan_at)->toBeNull()
        ->and(Transaksi::withoutGlobalScopes()->where('import_transaksi_id', $batch->id)->count())->toBe(1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(100000);
})->group('filament', 'import-transaksi', 'pembatalan-import');

test('riwayat import hanya menampilkan batch milik pengguna', function () {
    $data = siapkanDataImportTransaksi();
    $penggunaLain = siapkanDataImportTransaksi();
    $batchSendiri = ImportTransaksi::create([
        'user_id' => $data['user']->id,
        'nama_file' => 'sendiri.csv',
        'hash_file' => str_repeat('a', 64),
        'jumlah_baris' => 1,
        'status' => 'berhasil',
    ]);
    $batchLain = ImportTransaksi::create([
        'user_id' => $penggunaLain['user']->id,
        'nama_file' => 'lain.csv',
        'hash_file' => str_repeat('b', 64),
        'jumlah_baris' => 1,
        'status' => 'berhasil',
    ]);

    Livewire::actingAs($data['user'])
        ->test(ListImportTransaksis::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$batchSendiri])
        ->assertCanNotSeeTableRecords([$batchLain]);
})->group('filament', 'import-transaksi', 'riwayat-import');
