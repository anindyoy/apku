<?php

use App\Filament\Pages\Laporan;
use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Services\ImportTransaksiService;
use App\Services\TransaksiService;
use App\Services\TransferDompetService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/** @return array{user: \App\Models\User, bukuKas: BukuKas, dompet: Dompet, pengeluaran: Kategori} */
function siapkanDataKategoriOpsional(): array
{
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $bukuKas->update(['nama_buku' => 'Kas Utama', 'saldo' => 0, 'is_default' => true]);
    $dompet = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 0, 'is_default' => true]);
    $pengeluaran = Kategori::create(['user_id' => $user->id, 'nama' => 'Makanan', 'tipe' => 'Pengeluaran']);
    app(\App\Services\KategoriService::class)->hubungkan($pengeluaran, [$bukuKas->id]);

    return compact('user', 'bukuKas', 'dompet', 'pengeluaran');
}

test('transaksi dapat dibuat tanpa kategori dan tetap memperbarui saldo', function (mixed $kategoriKosong) {
    $data = siapkanDataKategoriOpsional();

    $transaksi = app(TransaksiService::class)->buat($data['user'], [
        'buku_kas_id' => $data['bukuKas']->id,
        'dompet_id' => $data['dompet']->id,
        'kategori_id' => $kategoriKosong,
        'nominal' => 15000,
        'tanggal' => now(),
    ], 'Pemasukan');

    expect($transaksi->kategori_id)->toBeNull()
        ->and($transaksi->namaKategoriLaporan())->toBe('Tanpa kategori')
        ->and(TransaksiResource::getKategoriLabel($transaksi))->toBeNull()
        ->and($data['bukuKas']->fresh()->saldo)->toBe(15000)
        ->and($data['dompet']->fresh()->saldo)->toBe(15000);
})->with([
    'null' => [null],
    'string kosong dari form' => [''],
])->group('kategori-opsional');

test('form tambah transaksi menerima kategori kosong', function () {
    $data = siapkanDataKategoriOpsional();

    Livewire::actingAs($data['user'])
        ->test(ListTransaksis::class)
        ->callAction('Tambah transaksi', data: [
            'jenis_form' => 'pengeluaran',
            'buku_kas_id' => $data['bukuKas']->id,
            'dompet_id' => $data['dompet']->id,
            'kategori_id' => null,
            'tanggal' => now()->subMinute()->format('Y-m-d H:i:s'),
            'nominal' => 7000,
        ])
        ->assertHasNoActionErrors();

    $transaksi = Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->sole();

    expect($transaksi->jenis)->toBe('Pengeluaran')
        ->and($transaksi->kategori_id)->toBeNull()
        ->and($data['bukuKas']->fresh()->saldo)->toBe(-7000);
})->group('kategori-opsional');

test('transfer kas dan transfer dompet selalu tanpa kategori', function () {
    $data = siapkanDataKategoriOpsional();
    $user = $data['user'];
    $kasTujuan = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Tabungan', 'saldo' => 0]);
    $dompetTujuan = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Bank', 'saldo' => 0]);
    $this->actingAs($user);

    $transferKas = app(TransaksiService::class)->transferBukuKas(
        $user, $data['bukuKas'], $kasTujuan, $data['dompet'], $data['dompet'], 5000,
    );
    app(TransferDompetService::class)->transfer($user, $data['dompet'], $dompetTujuan, $data['bukuKas'], 2000);

    $transfer = Transaksi::withoutGlobalScopes()->where('user_id', $user->id)->whereNotNull('transfer_code')->get();

    expect($transfer)->toHaveCount(4)
        ->and($transfer->whereNotNull('kategori_id'))->toHaveCount(0)
        ->and($transferKas['keluar']->namaKategoriLaporan())->toBe('Transfer');
})->group('kategori-opsional');

test('import menerima kolom kategori kosong atau tidak dipetakan sebagai tanpa kategori', function () {
    $data = siapkanDataKategoriOpsional();
    $service = app(ImportTransaksiService::class);
    $kosong = UploadedFile::fake()->createWithContent('kosong.csv', implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,,1000,Tanpa kategori',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Makanan,2000,Dengan kategori',
    ]));

    $hasil = $service->impor($data['user'], $kosong);
    $transaksi = Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->orderBy('nominal')->get();

    expect($hasil['jumlah_baris'])->toBe(2)
        ->and($transaksi[0]->kategori_id)->toBeNull()
        ->and($transaksi[1]->kategori_id)->toBe($data['pengeluaran']->id)
        ->and($data['bukuKas']->fresh()->saldo)->toBe(-3000);

    $tanpaKolom = UploadedFile::fake()->createWithContent('tanpa-kolom.csv', implode("\n", [
        'tanggal,jenis,buku_kas,dompet,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pemasukan,Kas Utama,Cash,4000,Tidak ada kolom kategori',
    ]));
    $pratinjau = $service->pratinjau($data['user'], $tanpaKolom);

    expect($pratinjau['errors'])->toBe([])
        ->and($pratinjau['baris'][0]['kategori_id'])->toBeNull()
        ->and($pratinjau['baris'][0]['nama_kategori_baru'])->toBeNull();
})->group('kategori-opsional');

test('import tetap menolak nama kategori yang tidak dikenal tanpa opsi buat otomatis', function () {
    $data = siapkanDataKategoriOpsional();
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        now()->subDay()->format('Y-m-d H:i').',Pengeluaran,Kas Utama,Cash,Belum Ada,1000,Kategori asing',
    ]));

    $hasil = app(ImportTransaksiService::class)->pratinjau($data['user'], $file);

    expect($hasil['errors'])->toHaveCount(1)
        ->and($hasil['errors'][0])->toContain('kolom kategori');
})->group('kategori-opsional');

test('laporan dan ekspor menampilkan baris tanpa kategori beserta persentasenya', function () {
    $data = siapkanDataKategoriOpsional();
    $user = $data['user'];
    $service = app(TransaksiService::class);
    $dasar = ['buku_kas_id' => $data['bukuKas']->id, 'dompet_id' => $data['dompet']->id, 'tanggal' => now()->startOfMonth()->addHours(8)];
    $service->buat($user, [...$dasar, 'kategori_id' => $data['pengeluaran']->id, 'nominal' => 750], 'Pengeluaran');
    $service->buat($user, [...$dasar, 'nominal' => 250, 'deskripsi' => 'Belum dikelompokkan'], 'Pengeluaran');

    $komponen = Livewire::actingAs($user)->test(Laporan::class)->call('muatLaporan');
    $laporan = $komponen->instance()->dataLaporan;
    $ringkasan = collect($laporan['kategoriPengeluaran'])->keyBy('nama');

    expect($ringkasan->keys()->all())->toBe(['Makanan', 'Tanpa kategori'])
        ->and($ringkasan['Tanpa kategori']['nominal'])->toBe(250)
        ->and($ringkasan['Tanpa kategori']['persen'])->toEqualWithDelta(25.0, 0.001)
        ->and(collect($laporan['rincianPengeluaran'])->firstWhere('nama', 'Tanpa kategori')['transaksi'])->toHaveCount(1)
        ->and($komponen->html())->toContain('Tanpa kategori');

    $respons = $komponen->instance()->unduhExcel();
    $pembaca = new XlsxReader;
    $pembaca->open($respons->getFile()->getPathname());
    $baris = [];

    foreach ($pembaca->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $baris[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
        }
    }

    $pembaca->close();
    $rincian = collect($baris)->first(fn (array $kolom): bool => ($kolom[5] ?? null) === 'Belum dikelompokkan');

    expect($rincian[4])->toBe('Tanpa kategori')
        ->and(view('laporan.pdf', [
            'laporan' => $laporan,
            'namaBuku' => 'Semua Kas',
            'namaDompet' => 'Semua Dompet',
            'tipePeriode' => 'Bulanan',
        ])->render())->toContain('Tanpa kategori');
})->group('kategori-opsional');
