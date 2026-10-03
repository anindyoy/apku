<?php

use App\Filament\Pages\Laporan;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Jobs\ProsesImportTransaksi;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\ImportTransaksi;
use App\Models\Kategori;
use App\Models\ShareBuku;
use App\Models\TabunganEmas;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\ImportTransaksiService;
use App\Services\TransaksiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Test pengaman perubahan Aktivitas menjadi Kategori
|--------------------------------------------------------------------------
|
| Mengunci perilaku import, laporan, pemindahan kas, dan hak akses kas bersama
| sebelum struktur kategori dirombak.
|
*/

/** @return array{user: User, bukuKas: BukuKas, dompet: Dompet, pemasukan: Kategori, pengeluaran: Kategori} */
function siapkanDataPengamanKategori(): array
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
    $pemasukan = Kategori::create(['user_id' => $user->id, 'nama' => 'Gaji', 'tipe' => 'Pemasukan']);
    $pengeluaran = Kategori::create(['user_id' => $user->id, 'nama' => 'Makanan', 'tipe' => 'Pengeluaran']);

    return compact('user', 'bukuKas', 'dompet', 'pemasukan', 'pengeluaran');
}

function catatTransaksiPengaman(
    User $user,
    BukuKas $bukuKas,
    Dompet $dompet,
    ?Kategori $kategori,
    string $jenis,
    int $nominal,
    ?string $tipeTransfer = null,
): Transaksi {
    return Transaksi::withoutEvents(fn () => Transaksi::create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'dompet_id' => $dompet->id,
        'kategori_id' => $kategori?->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => now()->startOfMonth()->addHours(9),
        'tipe_transfer' => $tipeTransfer,
    ]));
}

/** @return array<int, string> */
function barisCsvImportBesar(string $kategori): array
{
    $baris = ['tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi'];

    foreach (range(1, ImportTransaksiService::BATAS_BARIS_LANGSUNG + 1) as $nomor) {
        $baris[] = now()->subDay()->format('Y-m-d H:i').",Pemasukan,Kas Utama,Cash,{$kategori},1,Baris {$nomor}";
    }

    return $baris;
}

test('job import antrean membuat kategori baru sesuai pilihan batch', function () {
    Queue::fake();
    Storage::fake('local');
    $data = siapkanDataPengamanKategori();
    $service = app(ImportTransaksiService::class);
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", barisCsvImportBesar('Bonus Antrean')));

    $batch = $service->antrekan($data['user'], $file, buatKategoriOtomatis: true);

    expect($batch->buat_kategori_otomatis)->toBeTrue()
        ->and(Kategori::withoutGlobalScopes()->where('nama', 'Bonus Antrean')->exists())->toBeFalse();

    (new ProsesImportTransaksi($batch->id))->handle($service);

    $kategori = Kategori::withoutGlobalScopes()
        ->where('user_id', $data['user']->id)
        ->where('nama', 'Bonus Antrean')
        ->get();

    expect($kategori)->toHaveCount(1)
        ->and($kategori->first()->tipe)->toBe('Pemasukan')
        ->and($batch->fresh()->status)->toBe('berhasil')
        ->and(Transaksi::withoutGlobalScopes()
            ->where('import_transaksi_id', $batch->id)
            ->where('kategori_id', $kategori->first()->id)
            ->count())->toBe(ImportTransaksiService::BATAS_BARIS_LANGSUNG + 1)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(ImportTransaksiService::BATAS_BARIS_LANGSUNG + 1);
})->group('pengaman-kategori');

test('job import antrean melewati batch yang tidak lagi menunggu', function () {
    Queue::fake();
    Storage::fake('local');
    $data = siapkanDataPengamanKategori();
    $service = app(ImportTransaksiService::class);
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", barisCsvImportBesar('Gaji')));
    $batch = $service->antrekan($data['user'], $file);
    $batch->update(['status' => 'gagal']);

    (new ProsesImportTransaksi($batch->id))->handle($service);

    expect($batch->fresh()->status)->toBe('gagal')
        ->and(Transaksi::withoutGlobalScopes()->where('import_transaksi_id', $batch->id)->count())->toBe(0);
    Storage::disk('local')->assertExists($batch->path_file);
})->group('pengaman-kategori');

test('job import antrean yang gagal menandai batch dan menghapus file', function () {
    Queue::fake();
    Storage::fake('local');
    $data = siapkanDataPengamanKategori();
    $service = app(ImportTransaksiService::class);
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", barisCsvImportBesar('Gaji')));
    $batch = $service->antrekan($data['user'], $file);
    $pathFile = $batch->path_file;

    (new ProsesImportTransaksi($batch->id))->failed(new RuntimeException('Koneksi terputus'));

    $batch->refresh();
    expect($batch->status)->toBe('gagal')
        ->and($batch->pesan_error)->toBe('Koneksi terputus')
        ->and($batch->selesai_diproses_at)->not->toBeNull();
    Storage::disk('local')->assertMissing($pathFile);

    (new ProsesImportTransaksi(ImportTransaksi::withoutGlobalScopes()->max('id') + 100))->failed(null);
})->group('pengaman-kategori');

test('import menolak kategori dengan tipe yang tidak sesuai jenis transaksi', function () {
    $data = siapkanDataPengamanKategori();
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Gaji,1000,Tipe tertukar',
    ]));

    $hasil = app(ImportTransaksiService::class)->pratinjau($data['user'], $file);

    expect($hasil['errors'])->toHaveCount(1)
        ->and($hasil['baris'])->toBe([])
        ->and($hasil['baris_error'][0]['nomor_baris'])->toBe(2);
})->group('pengaman-kategori');

test('ringkasan laporan menghitung persentase per kategori sesuai filter kas dan dompet', function () {
    $data = siapkanDataPengamanKategori();
    $user = $data['user'];
    $kasLain = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Usaha', 'saldo' => 0]);
    $dompetLain = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $transport = Kategori::create(['user_id' => $user->id, 'nama' => 'Transportasi', 'tipe' => 'Pengeluaran']);

    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], $data['pengeluaran'], 'Pengeluaran', 300);
    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], $transport, 'Pengeluaran', 100);
    catatTransaksiPengaman($user, $data['bukuKas'], $dompetLain, $transport, 'Pengeluaran', 50);
    catatTransaksiPengaman($user, $kasLain, $data['dompet'], $data['pengeluaran'], 'Pengeluaran', 550);
    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], $data['pemasukan'], 'Pemasukan', 1000);

    $komponen = Livewire::actingAs($user)->test(Laporan::class)->call('muatLaporan');

    $semua = collect($komponen->instance()->dataLaporan['kategoriPengeluaran'])->keyBy('nama');
    expect($semua->keys()->all())->toBe(['Makanan', 'Transportasi'])
        ->and($semua['Makanan']['nominal'])->toBe(850)
        ->and($semua['Makanan']['persen'])->toEqualWithDelta(85.0, 0.001)
        ->and($semua['Transportasi']['persen'])->toEqualWithDelta(15.0, 0.001);

    $komponen->set('bukuKasId', (string) $data['bukuKas']->id);
    $perKas = collect($komponen->instance()->dataLaporan['kategoriPengeluaran'])->keyBy('nama');
    expect($perKas['Makanan']['nominal'])->toBe(300)
        ->and($perKas['Makanan']['persen'])->toEqualWithDelta(66.666, 0.01)
        ->and($perKas['Transportasi']['nominal'])->toBe(150);

    $komponen->set('dompetId', (string) $dompetLain->id);
    $laporan = $komponen->instance()->dataLaporan;
    expect($laporan['kategoriPengeluaran'])->toHaveCount(1)
        ->and($laporan['kategoriPengeluaran'][0]['nama'])->toBe('Transportasi')
        ->and($laporan['kategoriPengeluaran'][0]['persen'])->toEqualWithDelta(100.0, 0.001)
        ->and($laporan['kategoriPemasukan'])->toBe([])
        ->and($laporan['pengeluaran'])->toBe(50);
})->group('pengaman-kategori');

test('laporan mengelompokkan transaksi tanpa kategori dan transfer kas pada baris tersendiri', function () {
    $data = siapkanDataPengamanKategori();
    $user = $data['user'];

    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], $data['pengeluaran'], 'Pengeluaran', 100);
    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], null, 'Pengeluaran', 60);
    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], null, 'Transfer Pengeluaran', 40, 'buku_kas');
    catatTransaksiPengaman($user, $data['bukuKas'], $data['dompet'], null, 'Transfer Pengeluaran', 999, 'dompet');

    $laporan = Livewire::actingAs($user)->test(Laporan::class)->call('muatLaporan')->instance()->dataLaporan;
    $ringkasan = collect($laporan['kategoriPengeluaran'])->pluck('nominal', 'nama');
    $rincian = collect($laporan['rincianPengeluaran'])->mapWithKeys(fn (array $item): array => [
        $item['nama'] => $item['transaksi']->count(),
    ]);

    expect($ringkasan->all())->toBe(['Makanan' => 100, 'Tanpa kategori' => 60, 'Transfer' => 40])
        ->and($rincian->all())->toBe(['Makanan' => 1, 'Tanpa kategori' => 1, 'Transfer' => 1])
        ->and($laporan['pengeluaran'])->toBe(200);
})->group('pengaman-kategori');

test('pemindahan saat hapus kas mempertahankan kategori transaksi dan memindahkan tabungan emas', function () {
    $data = siapkanDataPengamanKategori();
    $user = $data['user'];
    $kasTujuan = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Tujuan', 'saldo' => 1000]);
    $transaksi = app(TransaksiService::class)->buat($user, [
        'buku_kas_id' => $data['bukuKas']->id,
        'dompet_id' => $data['dompet']->id,
        'kategori_id' => $data['pemasukan']->id,
        'nominal' => 5000,
        'tanggal' => now(),
    ], 'Pemasukan');
    $emas = TabunganEmas::factory()->create(['buku_kas_id' => $data['bukuKas']->id]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callTableAction('hapusDanPindahkan', $data['bukuKas'], data: ['buku_kas_id' => $kasTujuan->id])
        ->assertHasNoTableActionErrors();

    expect(BukuKas::withoutGlobalScopes()->find($data['bukuKas']->id))->toBeNull()
        ->and($transaksi->fresh()->buku_kas_id)->toBe($kasTujuan->id)
        ->and($transaksi->fresh()->kategori_id)->toBe($data['pemasukan']->id)
        ->and($emas->fresh()->buku_kas_id)->toBe($kasTujuan->id)
        ->and($kasTujuan->fresh()->saldo)->toBe(6000);
})->group('pengaman-kategori');

test('policy kas hanya mengizinkan pemilik mengubah dan menghapus kas', function (string $peran, bool $bolehMelihat) {
    $pemilik = createRegularUserWithBukuKas();
    $anggota = createRegularUserWithBukuKas();
    $kas = $pemilik->buku_kas()->firstOrFail();

    if ($peran !== 'asing') {
        ShareBuku::create([
            'buku_kas_id' => $kas->id,
            'user_id' => $anggota->id,
            'invited_by_user_id' => $pemilik->id,
            'privilege' => $peran,
            'berlaku_mulai' => now()->subMinute(),
        ]);
    }

    expect($anggota->can('view', $kas))->toBe($bolehMelihat)
        ->and($anggota->can('update', $kas))->toBeFalse()
        ->and($anggota->can('delete', $kas))->toBeFalse()
        ->and($pemilik->can('view', $kas))->toBeTrue()
        ->and($pemilik->can('update', $kas))->toBeTrue()
        ->and($pemilik->can('delete', $kas))->toBeTrue();
})->with([
    'editor' => ['editor', true],
    'viewer' => ['viewer', true],
    'pengguna asing' => ['asing', false],
])->group('pengaman-kategori');

test('editor kas bersama dapat membuat mengubah dan menghapus transaksinya sendiri', function () {
    $pemilik = createRegularUserWithBukuKas();
    $editor = createRegularUserWithBukuKas();
    $kas = $pemilik->buku_kas()->firstOrFail();
    $saldoAwal = $kas->saldo;
    $dompet = Dompet::factory()->create(['user_id' => $editor->id, 'saldo' => 0]);
    $kategori = Kategori::factory()->create(['user_id' => $editor->id, 'tipe' => 'Pengeluaran']);
    ShareBuku::create([
        'buku_kas_id' => $kas->id,
        'user_id' => $editor->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'editor',
        'berlaku_mulai' => now()->subMinute(),
    ]);
    $this->actingAs($editor);
    $service = app(TransaksiService::class);

    $transaksi = $service->buat($editor, [
        'buku_kas_id' => $kas->id,
        'dompet_id' => $dompet->id,
        'kategori_id' => $kategori->id,
        'nominal' => 20000,
        'tanggal' => now(),
    ], 'Pengeluaran');
    expect($kas->fresh()->saldo)->toBe($saldoAwal - 20000);

    $service->ubah($editor, $transaksi, ['nominal' => 5000]);
    expect($kas->fresh()->saldo)->toBe($saldoAwal - 5000)
        ->and($transaksi->fresh()->nominal)->toBe(5000);

    $service->hapus($editor, $transaksi->fresh());
    expect($kas->fresh()->saldo)->toBe($saldoAwal)
        ->and(Transaksi::withoutGlobalScopes()->find($transaksi->id))->toBeNull();
})->group('pengaman-kategori');

test('viewer kas bersama tidak dapat mencatat transaksi', function () {
    $pemilik = createRegularUserWithBukuKas();
    $viewer = createRegularUserWithBukuKas();
    $kas = $pemilik->buku_kas()->firstOrFail();
    $dompet = Dompet::factory()->create(['user_id' => $viewer->id, 'saldo' => 0]);
    $kategori = Kategori::factory()->create(['user_id' => $viewer->id, 'tipe' => 'Pemasukan']);
    ShareBuku::create([
        'buku_kas_id' => $kas->id,
        'user_id' => $viewer->id,
        'invited_by_user_id' => $pemilik->id,
        'privilege' => 'viewer',
        'berlaku_mulai' => now()->subMinute(),
    ]);
    $this->actingAs($viewer);

    expect(fn () => app(TransaksiService::class)->buat($viewer, [
        'buku_kas_id' => $kas->id,
        'dompet_id' => $dompet->id,
        'kategori_id' => $kategori->id,
        'nominal' => 1000,
        'tanggal' => now(),
    ], 'Pemasukan'))->toThrow(AuthorizationException::class)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $viewer->id)->where('buku_kas_id', $kas->id)->exists())->toBeFalse();
})->group('pengaman-kategori');
