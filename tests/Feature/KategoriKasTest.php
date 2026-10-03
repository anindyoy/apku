<?php

use App\Filament\Pages\Kategori as HalamanKategori;
use App\Filament\Pages\Onboarding;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Kategori;
use App\Models\ShareBuku;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BukuKasService;
use App\Services\ImportTransaksiService;
use App\Services\KategoriService;
use App\Services\TransaksiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** @return array{user: User, kas: BukuKas, kasLain: BukuKas, dompet: Dompet} */
function siapkanDataKategoriKas(): array
{
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->firstOrFail();
    $kas->update(['nama_buku' => 'Kas Utama', 'saldo' => 0, 'is_default' => true]);
    $kasLain = BukuKas::create(['user_id' => $user->id, 'nama_buku' => 'Kas Usaha', 'saldo' => 0]);
    $dompet = Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 0, 'is_default' => true]);

    return compact('user', 'kas', 'kasLain', 'dompet');
}

function bagikanKas(BukuKas $kas, User $anggota, string $peran): ShareBuku
{
    return ShareBuku::create([
        'buku_kas_id' => $kas->id,
        'user_id' => $anggota->id,
        'invited_by_user_id' => $kas->user_id,
        'privilege' => $peran,
        'berlaku_mulai' => now()->subMinute(),
    ]);
}

function catatDenganKategori(User $user, BukuKas $kas, Dompet $dompet, ?Kategori $kategori, int $nominal = 1000): Transaksi
{
    return app(TransaksiService::class)->buat($user, [
        'buku_kas_id' => $kas->id,
        'dompet_id' => $dompet->id,
        'kategori_id' => $kategori?->id,
        'nominal' => $nominal,
        'tanggal' => now(),
    ], 'Pengeluaran');
}

test('kategori dibuat untuk kas pilihan dan satu kategori dapat dipakai di beberapa kas', function () {
    $data = siapkanDataKategoriKas();
    $service = app(KategoriService::class);

    $kategori = $service->buat($data['user'], ['nama' => ' Belanja ', 'tipe' => 'Pengeluaran'], [$data['kas']->id, $data['kasLain']->id]);

    expect($kategori->nama)->toBe('Belanja')
        ->and($kategori->user_id)->toBe($data['user']->id)
        ->and($kategori->dibuat_oleh)->toBe($data['user']->id)
        ->and($kategori->idKas())->toEqualCanonicalizing([$data['kas']->id, $data['kasLain']->id])
        ->and($data['kas']->kategori()->pluck('kategori.id')->all())->toBe([$kategori->id])
        ->and(catatDenganKategori($data['user'], $data['kasLain'], $data['dompet'], $kategori)->kategori_id)->toBe($kategori->id);

    expect(fn () => $service->buat($data['user'], ['nama' => 'belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]))
        ->toThrow(ValidationException::class);
})->group('kategori-kas');

test('transaksi menolak kategori yang tidak terhubung ke kasnya', function () {
    $data = siapkanDataKategoriKas();
    $kategori = app(KategoriService::class)->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $service = app(TransaksiService::class);

    expect(fn () => catatDenganKategori($data['user'], $data['kasLain'], $data['dompet'], $kategori))
        ->toThrow(ValidationException::class)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->count())->toBe(0);

    $transaksi = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $kategori);

    // Pindah kas tanpa mengganti kategori ditolak; mengosongkannya diizinkan.
    expect(fn () => $service->ubah($data['user'], $transaksi, ['buku_kas_id' => $data['kasLain']->id]))
        ->toThrow(ValidationException::class);

    $diubah = $service->ubah($data['user'], $transaksi, ['buku_kas_id' => $data['kasLain']->id, 'kategori_id' => null]);

    expect($diubah->buku_kas_id)->toBe($data['kasLain']->id)
        ->and($diubah->kategori_id)->toBeNull()
        ->and($data['kasLain']->fresh()->saldo)->toBe(-1000)
        ->and($data['kas']->fresh()->saldo)->toBe(0);
})->group('kategori-kas');

test('editor mengelola kategori kas bersama atas nama pemilik kas', function () {
    $data = siapkanDataKategoriKas();
    $editor = createRegularUserWithBukuKas();
    $dompetEditor = Dompet::create(['user_id' => $editor->id, 'nama_dompet' => 'Dompet Editor', 'saldo' => 0, 'is_default' => true]);
    bagikanKas($data['kas'], $editor, 'editor');
    $this->actingAs($editor);
    $service = app(KategoriService::class);

    $kategori = $service->buat($editor, ['nama' => 'Iuran', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $milikEditor = $service->buat($editor, ['nama' => 'Pribadi', 'tipe' => 'Pengeluaran'], [$editor->buku_kas()->value('id')]);

    expect($kategori->user_id)->toBe($data['user']->id)
        ->and($kategori->dibuat_oleh)->toBe($editor->id)
        ->and($editor->can('update', $kategori))->toBeTrue()
        ->and($editor->can('delete', $kategori))->toBeTrue()
        ->and(Kategori::query()->pluck('nama')->all())->toEqualCanonicalizing(['Iuran', 'Pribadi'])
        ->and(catatDenganKategori($editor, $data['kas'], $dompetEditor, $kategori)->kategori_id)->toBe($kategori->id);

    // Kategori pribadi Editor tidak berlaku di kas bersama.
    expect(fn () => catatDenganKategori($editor, $data['kas'], $dompetEditor, $milikEditor))
        ->toThrow(ValidationException::class);

    // Kas dari pemilik berbeda tidak dapat digabung dalam satu kategori.
    expect(fn () => $service->buat($editor, ['nama' => 'Campur', 'tipe' => 'Pengeluaran'], [$data['kas']->id, $editor->buku_kas()->value('id')]))
        ->toThrow(ValidationException::class);

    $service->ubah($editor, $kategori, ['nama' => 'Iuran Bulanan']);
    expect($kategori->fresh()->nama)->toBe('Iuran Bulanan');

    // Kategori yang juga terhubung ke kas tanpa akses Editor hanya dapat dikelola pemilik.
    $service->hubungkan($kategori, [$data['kasLain']->id]);
    expect($editor->can('update', $kategori->fresh()))->toBeFalse()
        ->and($editor->can('delete', $kategori->fresh()))->toBeFalse()
        ->and(fn () => $service->ubah($editor, $kategori->fresh(), ['nama' => 'Dicoba']))->toThrow(AuthorizationException::class)
        ->and($data['user']->can('update', $kategori->fresh()))->toBeTrue();
})->group('kategori-kas', 'policy-kategori');

test('viewer dan pengguna lain tidak dapat mengelola kategori kas', function (string $peran) {
    $data = siapkanDataKategoriKas();
    $anggota = createRegularUserWithBukuKas();
    $kategori = app(KategoriService::class)->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);

    if ($peran === 'viewer') {
        bagikanKas($data['kas'], $anggota, 'viewer');
    }

    $this->actingAs($anggota);
    $service = app(KategoriService::class);

    expect($anggota->can('view', $kategori))->toBe($peran === 'viewer')
        ->and($anggota->can('update', $kategori))->toBeFalse()
        ->and($anggota->can('delete', $kategori))->toBeFalse()
        ->and(Kategori::query()->whereKey($kategori->id)->exists())->toBe($peran === 'viewer')
        ->and(fn () => $service->buat($anggota, ['nama' => 'Titipan', 'tipe' => 'Pengeluaran'], [$data['kas']->id]))->toThrow(AuthorizationException::class)
        ->and(fn () => $service->ubah($anggota, $kategori, ['nama' => 'Diubah']))->toThrow(AuthorizationException::class)
        ->and(fn () => $service->hapus($anggota, $kategori))->toThrow(AuthorizationException::class)
        ->and(fn () => $service->lepasDariKas($anggota, $kategori, $data['kas'], true))->toThrow(AuthorizationException::class)
        ->and($kategori->fresh()->nama)->toBe('Belanja');
})->with(['viewer', 'asing'])->group('kategori-kas', 'policy-kategori');

test('admin tidak mengelola kategori pengguna', function () {
    $data = siapkanDataKategoriKas();
    $kategori = app(KategoriService::class)->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $admin = User::query()->admin()->firstOrFail();

    expect($admin->can('viewAny', Kategori::class))->toBeFalse()
        ->and($admin->can('create', Kategori::class))->toBeFalse()
        ->and($admin->can('update', $kategori))->toBeFalse()
        ->and($admin->can('delete', $kategori))->toBeFalse();
})->group('kategori-kas', 'policy-kategori');

test('melepas kategori dari kas yang masih memakainya diblokir sampai transaksinya dikosongkan', function () {
    $data = siapkanDataKategoriKas();
    $service = app(KategoriService::class);
    $kategori = $service->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id, $data['kasLain']->id]);
    $transaksi = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $kategori);
    $transaksiLain = catatDenganKategori($data['user'], $data['kasLain'], $data['dompet'], $kategori);

    expect(fn () => $service->ubah($data['user'], $kategori, [], [$data['kasLain']->id]))->toThrow(ValidationException::class)
        ->and(fn () => $service->lepasDariKas($data['user'], $kategori, $data['kas']))->toThrow(ValidationException::class)
        ->and($kategori->idKas())->toEqualCanonicalizing([$data['kas']->id, $data['kasLain']->id])
        ->and($transaksi->fresh()->kategori_id)->toBe($kategori->id);

    $jumlah = $service->lepasDariKas($data['user'], $kategori, $data['kas'], kosongkanTransaksi: true);

    expect($jumlah)->toBe(1)
        ->and($kategori->idKas())->toBe([$data['kasLain']->id])
        ->and($transaksi->fresh()->kategori_id)->toBeNull()
        ->and($transaksiLain->fresh()->kategori_id)->toBe($kategori->id)
        ->and($data['kas']->fresh()->saldo)->toBe(-1000);

    // Kas tanpa transaksi terkait dapat dilepas langsung melalui perubahan kategori.
    $service->ubah($data['user'], $kategori, [], [$data['kas']->id, $data['kasLain']->id]);
    $service->ubah($data['user'], $kategori, [], [$data['kasLain']->id]);
    expect($kategori->idKas())->toBe([$data['kasLain']->id]);
})->group('kategori-kas');

test('menghapus kategori memindahkan transaksi ke pengganti atau mengosongkannya', function () {
    $data = siapkanDataKategoriKas();
    $service = app(KategoriService::class);
    $lama = $service->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $pengganti = $service->buat($data['user'], ['nama' => 'Kebutuhan', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $tidakCocok = $service->buat($data['user'], ['nama' => 'Usaha', 'tipe' => 'Pengeluaran'], [$data['kasLain']->id]);
    $transaksi = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $lama);

    expect(fn () => $service->hapus($data['user'], $lama, $tidakCocok->id))->toThrow(ValidationException::class)
        ->and(Kategori::withoutGlobalScopes()->find($lama->id))->not->toBeNull();

    $service->hapus($data['user'], $lama, $pengganti->id);
    expect(Kategori::withoutGlobalScopes()->find($lama->id))->toBeNull()
        ->and($transaksi->fresh()->kategori_id)->toBe($pengganti->id)
        ->and(DB::table('kategori_kas')->where('kategori_id', $lama->id)->exists())->toBeFalse();

    $service->hapus($data['user'], $pengganti);
    expect($transaksi->fresh()->kategori_id)->toBeNull()
        ->and($transaksi->fresh()->namaKategoriLaporan())->toBe('Tanpa kategori');
})->group('kategori-kas');

test('hapus kas memetakan kategori ke kas tujuan atau mengosongkannya', function () {
    $data = siapkanDataKategoriKas();
    $service = app(KategoriService::class);
    $bersama = $service->buat($data['user'], ['nama' => 'Bersama', 'tipe' => 'Pengeluaran'], [$data['kas']->id, $data['kasLain']->id]);
    $dipetakan = $service->buat($data['user'], ['nama' => 'Dipetakan', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $dikosongkan = $service->buat($data['user'], ['nama' => 'Dikosongkan', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $tujuan = $service->buat($data['user'], ['nama' => 'Tujuan', 'tipe' => 'Pengeluaran'], [$data['kasLain']->id]);
    $transaksiBersama = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $bersama, 100);
    $transaksiDipetakan = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $dipetakan, 200);
    $transaksiDikosongkan = catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $dikosongkan, 300);

    expect(app(BukuKasService::class)->kategoriPerluDipetakan($data['kas'], $data['kasLain']))
        ->toBe([$dikosongkan->id => 'Dikosongkan', $dipetakan->id => 'Dipetakan']);

    // Kategori pengganti yang tidak terhubung ke kas tujuan ditolak dan tidak mengubah data.
    expect(fn () => app(BukuKasService::class)->pindahkanDanHapus($data['user'], $data['kas'], $data['kasLain'], [
        $dipetakan->id => $dikosongkan->id,
    ]))->toThrow(ValidationException::class)
        ->and($transaksiDipetakan->fresh()->buku_kas_id)->toBe($data['kas']->id);

    Livewire::actingAs($data['user'])
        ->test(ListBukuKas::class)
        ->callTableAction('hapusDanPindahkan', $data['kas'], data: [
            'buku_kas_id' => $data['kasLain']->id,
            'pemetaan_kategori' => [$dipetakan->id => $tujuan->id, $dikosongkan->id => null],
        ])
        ->assertHasNoTableActionErrors();

    expect(BukuKas::withoutGlobalScopes()->find($data['kas']->id))->toBeNull()
        ->and($transaksiBersama->fresh()->only(['buku_kas_id', 'kategori_id']))->toBe(['buku_kas_id' => $data['kasLain']->id, 'kategori_id' => $bersama->id])
        ->and($transaksiDipetakan->fresh()->only(['buku_kas_id', 'kategori_id']))->toBe(['buku_kas_id' => $data['kasLain']->id, 'kategori_id' => $tujuan->id])
        ->and($transaksiDikosongkan->fresh()->only(['buku_kas_id', 'kategori_id']))->toBe(['buku_kas_id' => $data['kasLain']->id, 'kategori_id' => null])
        ->and($data['kasLain']->fresh()->saldo)->toBe(-600)
        ->and($dipetakan->fresh()->idKas())->toBe([]);
})->group('kategori-kas');

test('onboarding menghubungkan kategori awal ke kas utama', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->fillForm([
            'nama_buku' => 'Kas Rumah',
            'saldo_awal' => 0,
            'kategori_pemasukan' => [['nama' => 'Gaji'], ['nama' => 'Bonus']],
            'kategori_pengeluaran' => [['nama' => 'Makan'], ['nama' => 'Listrik']],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $kas = $user->buku_kas()->firstOrFail();
    $kategori = Kategori::withoutGlobalScopes()->where('user_id', $user->id)->get();

    expect($kategori)->toHaveCount(4)
        ->and($kategori->every(fn (Kategori $item): bool => $item->idKas() === [$kas->id] && $item->dibuat_oleh === $user->id))->toBeTrue();
})->group('kategori-kas');

test('kas baru dapat langsung memakai seluruh kategori pemilik atau dibiarkan tanpa kategori', function (bool $hubungkan) {
    $user = createRegularUserWithBukuKas();
    Dompet::create(['user_id' => $user->id, 'nama_dompet' => 'Cash', 'saldo' => 0, 'is_default' => true]);
    $kategori = app(KategoriService::class)->buat($user, ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$user->buku_kas()->value('id')]);

    Livewire::actingAs($user)
        ->test(ListBukuKas::class)
        ->callAction('create', data: ['nama_buku' => 'Kas Baru', 'saldo' => 0, 'hubungkan_kategori' => $hubungkan])
        ->assertHasNoActionErrors();

    $kasBaru = BukuKas::withoutGlobalScopes()->where('user_id', $user->id)->where('nama_buku', 'Kas Baru')->firstOrFail();

    expect($kategori->terhubungKe($kasBaru->id))->toBe($hubungkan);
})->with([true, false])->group('kategori-kas');

test('import mencari kategori pada kas baris dan membuat kategori baru di kas tersebut', function () {
    $data = siapkanDataKategoriKas();
    $service = app(KategoriService::class);
    $makan = $service->buat($data['user'], ['nama' => 'Makan', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $kemarin = now()->subDay()->format('Y-m-d H:i');
    $file = UploadedFile::fake()->createWithContent('transaksi.csv', implode("\n", [
        'tanggal,jenis,buku_kas,dompet,kategori,nominal,deskripsi',
        "{$kemarin},Pengeluaran,Kas Utama,Cash,Makan,1000,Terhubung",
        "{$kemarin},Pengeluaran,Kas Usaha,Cash,Makan,2000,Belum terhubung",
        "{$kemarin},Pengeluaran,Kas Usaha,Cash,Sewa,3000,Kategori baru",
    ]));
    $import = app(ImportTransaksiService::class);

    $tanpaOpsi = $import->pratinjau($data['user'], $file);
    expect($tanpaOpsi['errors'])->toHaveCount(2)
        ->and($tanpaOpsi['errors'][0])->toContain('Baris 3', 'belum terhubung ke kas Kas Usaha');

    $denganOpsi = $import->pratinjau($data['user'], $file, buatKategoriOtomatis: true);
    expect($denganOpsi['errors'])->toBe([])
        ->and(array_values($denganOpsi['kategori_baru']['Pengeluaran']))->toBe(['Sewa (Kas Usaha)'])
        ->and(array_values($denganOpsi['kategori_dihubungkan']))->toBe(['Makan → Kas Usaha'])
        ->and($makan->idKas())->toBe([$data['kas']->id]);

    $import->impor($data['user'], $file, buatKategoriOtomatis: true);
    $sewa = Kategori::withoutGlobalScopes()->where('user_id', $data['user']->id)->where('nama', 'Sewa')->sole();
    $transaksi = Transaksi::withoutGlobalScopes()->where('user_id', $data['user']->id)->orderBy('nominal')->get();

    expect($makan->idKas())->toEqualCanonicalizing([$data['kas']->id, $data['kasLain']->id])
        ->and($sewa->idKas())->toBe([$data['kasLain']->id])
        ->and($sewa->dibuat_oleh)->toBe($data['user']->id)
        ->and($transaksi->pluck('kategori_id')->all())->toBe([$makan->id, $makan->id, $sewa->id])
        ->and(Kategori::withoutGlobalScopes()->where('user_id', $data['user']->id)->where('nama', 'Makan')->count())->toBe(1);
})->group('kategori-kas', 'import-transaksi');

test('opsi kategori mengikuti kas dan diperbarui saat kategori atau hubungannya berubah', function () {
    $data = siapkanDataKategoriKas();
    $this->actingAs($data['user']);
    $service = app(KategoriService::class);

    expect(Transaksi::opsiKategori($data['kas']->id, 'Pengeluaran'))->toBe([]);

    $kategori = $service->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    expect(Transaksi::opsiKategori($data['kas']->id, 'Pengeluaran'))->toBe([$kategori->id => 'Belanja'])
        ->and(Transaksi::opsiKategori($data['kas']->id, 'Pemasukan'))->toBe([])
        ->and(Transaksi::opsiKategori($data['kasLain']->id, 'Pengeluaran'))->toBe([])
        ->and(Transaksi::opsiKategori(null, 'Pengeluaran'))->toBe([]);

    $service->ubah($data['user'], $kategori, ['nama' => 'Belanja Harian'], [$data['kas']->id, $data['kasLain']->id]);
    expect(Transaksi::opsiKategori($data['kas']->id, 'Pengeluaran'))->toBe([$kategori->id => 'Belanja Harian'])
        ->and(Transaksi::opsiKategori($data['kasLain']->id, 'Pengeluaran'))->toBe([$kategori->id => 'Belanja Harian']);

    $service->lepasDariKas($data['user'], $kategori, $data['kasLain']);
    expect(Transaksi::opsiKategori($data['kasLain']->id, 'Pengeluaran'))->toBe([]);

    $service->hapus($data['user'], $kategori);
    expect(Transaksi::opsiKategori($data['kas']->id, 'Pengeluaran'))->toBe([]);

    // Kas yang tidak dapat dikelola pengguna tidak membuka daftar kategorinya.
    $pemilikLain = createRegularUserWithBukuKas();
    $kasAsing = BukuKas::withoutGlobalScopes()->where('user_id', $pemilikLain->id)->firstOrFail();
    $service->buat($pemilikLain, ['nama' => 'Rahasia', 'tipe' => 'Pengeluaran'], [$kasAsing->id]);
    expect(Transaksi::opsiKategori($kasAsing->id, 'Pengeluaran'))->toBe([]);
})->group('kategori-kas', 'opsi-select-cache');

test('halaman kategori menampilkan dan mengelola kategori sesuai kas yang dapat diakses', function () {
    $data = siapkanDataKategoriKas();
    $editor = createRegularUserWithBukuKas();
    bagikanKas($data['kas'], $editor, 'editor');
    $service = app(KategoriService::class);
    $milikPemilik = $service->buat($data['user'], ['nama' => 'Belanja', 'tipe' => 'Pengeluaran'], [$data['kas']->id]);
    $tersembunyi = $service->buat($data['user'], ['nama' => 'Privat', 'tipe' => 'Pengeluaran'], [$data['kasLain']->id]);

    $halaman = Livewire::actingAs($editor)->test(HalamanKategori::class)->assertSuccessful();
    expect($halaman->instance()->getTableRecords()->pluck('id')->all())->toBe([$milikPemilik->id]);

    $halaman->callTableAction('tambah', data: ['nama' => 'Iuran', 'tipe' => 'Pemasukan', 'kas' => [$data['kas']->id]])
        ->assertHasNoTableActionErrors();
    $iuran = Kategori::withoutGlobalScopes()->where('nama', 'Iuran')->sole();
    expect($iuran->user_id)->toBe($data['user']->id)
        ->and($iuran->dibuat_oleh)->toBe($editor->id);

    $halaman->callTableAction('ubah', $iuran, data: ['nama' => 'Iuran Warga', 'tipe' => 'Pemasukan', 'kas' => [$data['kas']->id]])
        ->assertHasNoTableActionErrors();
    expect($iuran->fresh()->nama)->toBe('Iuran Warga');

    $halaman->callTableAction('hapus', $iuran)->assertHasNoTableActionErrors();
    expect(Kategori::withoutGlobalScopes()->find($iuran->id))->toBeNull()
        ->and(Kategori::withoutGlobalScopes()->find($tersembunyi->id))->not->toBeNull();

    $halamanPemilik = Livewire::actingAs($data['user'])->test(HalamanKategori::class);
    $kolom = $halamanPemilik->instance()->getTable()->getColumn('nama')->record($milikPemilik->loadCount('transaksi'));
    expect($halamanPemilik->instance()->getTableRecords()->pluck('nama')->all())->toBe(['Belanja', 'Privat'])
        ->and($kolom->getDescriptionBelow())->toBe('0 transaksi');

    catatDenganKategori($data['user'], $data['kas'], $data['dompet'], $milikPemilik);
    $halamanPemilik->callTableAction('lepasDariKas', $milikPemilik, data: ['buku_kas_id' => $data['kas']->id, 'kosongkan' => false]);
    expect($milikPemilik->idKas())->toBe([$data['kas']->id]);

    // Aksi yang ditolak tetap terbuka, jadi percobaan berikutnya memakai komponen baru.
    Livewire::actingAs($data['user'])->test(HalamanKategori::class)
        ->callTableAction('lepasDariKas', $milikPemilik, data: ['buku_kas_id' => $data['kas']->id, 'kosongkan' => true])
        ->assertHasNoTableActionErrors();
    expect($milikPemilik->idKas())->toBe([]);
})->group('kategori-kas', 'filament');
