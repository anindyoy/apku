<?php

use App\Filament\Resources\ImportTransaksiResource\Pages\ListImportTransaksis;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\Dompet;
use App\Models\ImportTransaksi;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Services\ImportTransaksiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

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

test('pratinjau xlsx tidak mengubah transaksi atau saldo', function () {
    $data = siapkanDataImportTransaksi();
    $path = tempnam(sys_get_temp_dir(), 'import-transaksi-test-').'.xlsx';
    app(ImportTransaksiService::class)->buatTemplateXlsx($path);

    try {
        $hasil = app(ImportTransaksiService::class)->pratinjau($data['user'], $path);
    } finally {
        @unlink($path);
    }

    expect($hasil['jumlah_baris'])->toBe(1)
        ->and($hasil['errors'])->toBe([])
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(0)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(0)
        ->and($data['dompet']->fresh()->saldo)->toBe(0);
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

test('halaman transaksi menyediakan aksi import dan unduh template', function () {
    $data = siapkanDataImportTransaksi();

    Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertActionExists('Import Transaksi')
        ->assertActionExists('Unduh Template Import')
        ->assertActionExists('Riwayat Import');
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
