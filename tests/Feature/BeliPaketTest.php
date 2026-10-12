<?php

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Onboarding;
use App\Filament\Resources\LanggananResource;
use App\Http\Controllers\BeliPaketController;
use App\Livewire\PilihPaketLangganan;
use App\Models\BukuKas;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\SumberDana;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

// Membersihkan paket dan order agar skenario beli-paket terkendali.
function bersihkanDataBeliPaket(): void
{
    Langganan::query()->delete();
    VoucherCode::query()->delete();
    Voucher::query()->delete();
    PaketLangganan::query()->delete();
    MetodePembayaran::query()->delete();
}

// Membuat pengguna non-admin yang sudah menyelesaikan onboarding (punya kas dan sumber dana).
function buatPenggunaSelesaiOnboarding(): User
{
    $pengguna = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

    BukuKas::factory()->create(['user_id' => $pengguna->id, 'nama_buku' => 'Kas Uji Beli']);
    SumberDana::factory()->create(['user_id' => $pengguna->id, 'nama_dompet' => 'Sumber Uji Beli']);

    return $pengguna;
}

test('tamu beli paket aktif diarahkan ke registrasi dengan session terisi', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);

    $registrasiUrl = filament()->getRegistrationUrl() ?? route('filament.admin.auth.register');

    $this->get(route('beli-paket', $paket))
        ->assertRedirect($registrasiUrl)
        ->assertSessionHas(BeliPaketController::SESSION_KEY, $paket->id);
})->group('langganan', 'beli-paket');

test('paket tidak ada atau nonaktif kembali ke harga dengan pesan', function () {
    bersihkanDataBeliPaket();
    $nonaktif = PaketLangganan::factory()->create(['is_active' => false]);
    $tujuan = route('home').'#harga';

    $this->get(route('beli-paket', $nonaktif))
        ->assertRedirect($tujuan)
        ->assertSessionHas('paket_gagal');

    expect(session()->has(BeliPaketController::SESSION_KEY))->toBeFalse();

    $this->get('/beli/999999')
        ->assertRedirect($tujuan)
        ->assertSessionHas('paket_gagal');

    expect(session()->has(BeliPaketController::SESSION_KEY))->toBeFalse();
})->group('langganan', 'beli-paket');

test('pengguna login diarahkan ke langganan atau onboarding sesuai status', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);

    $selesai = buatPenggunaSelesaiOnboarding();

    $this->actingAs($selesai)->get(route('beli-paket', $paket))
        ->assertRedirect(LanggananResource::getUrl('index', ['paket' => $paket->id]));

    $baru = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

    $this->actingAs($baru)->get(route('beli-paket', $paket))
        ->assertRedirect(Onboarding::getUrl());

    expect($baru->fresh()->paket_langganan_dipilih_id)->toBe($paket->id);
})->group('langganan', 'beli-paket');

test('admin beli paket diarahkan ke dasbor tanpa menyimpan pilihan', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)->get(route('beli-paket', $paket))
        ->assertRedirect(filament()->getUrl());

    expect($admin->fresh()->paket_langganan_dipilih_id)->toBeNull();
    expect(session()->has(BeliPaketController::SESSION_KEY))->toBeFalse();
})->group('langganan', 'beli-paket');

test('registrasi memindahkan session paket ke kolom users dan membersihkan session', function () {
    config()->set('app.env', 'local');
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    session()->put(BeliPaketController::SESSION_KEY, $paket->id);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Pembeli Paket',
            'email' => 'pembeli.paket@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ])
        ->call('register')
        ->assertHasNoErrors();

    $pengguna = User::where('email', 'pembeli.paket@example.com')->firstOrFail();

    expect((int) $pengguna->paket_langganan_dipilih_id)->toBe($paket->id);
    expect(session()->has(BeliPaketController::SESSION_KEY))->toBeFalse();
})->group('langganan', 'beli-paket');

test('registrasi tanpa session membiarkan kolom paket kosong', function () {
    config()->set('app.env', 'local');
    bersihkanDataBeliPaket();

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Tanpa Paket',
            'email' => 'tanpa.paket@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ])
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'tanpa.paket@example.com')->firstOrFail()->paket_langganan_dipilih_id)->toBeNull();
})->group('langganan', 'beli-paket');

test('registrasi dengan paket nonaktif membiarkan kolom paket kosong', function () {
    config()->set('app.env', 'local');
    bersihkanDataBeliPaket();
    $nonaktif = PaketLangganan::factory()->create(['is_active' => false]);

    session()->put(BeliPaketController::SESSION_KEY, $nonaktif->id);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Paket Basi',
            'email' => 'paket.basi@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
        ])
        ->call('register')
        ->assertHasNoErrors();

    expect(User::where('email', 'paket.basi@example.com')->firstOrFail()->paket_langganan_dipilih_id)->toBeNull();
    expect(session()->has(BeliPaketController::SESSION_KEY))->toBeFalse();
})->group('langganan', 'beli-paket');

test('selesai onboarding dengan paket tersimpan menuju langganan dengan paket', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    $pengguna = User::factory()->create([
        'role' => 'user',
        'email_verified_at' => now(),
        'paket_langganan_dipilih_id' => $paket->id,
    ]);

    Livewire::actingAs($pengguna)
        ->test(Onboarding::class)
        ->fillForm([
            'nama_buku' => 'Kas Beli Paket',
            'description' => 'Kas untuk uji beli paket',
            'saldo_awal' => 50000,
            'kategori_pemasukan' => [['nama' => 'Gaji Uji'], ['nama' => 'Bonus Uji']],
            'kategori_pengeluaran' => [['nama' => 'Makan Uji'], ['nama' => 'Transport Uji']],
        ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(LanggananResource::getUrl('index', ['paket' => $paket->id]));
})->group('langganan', 'beli-paket');

test('selesai onboarding tanpa paket aktif menuju dasbor', function () {
    bersihkanDataBeliPaket();
    $nonaktif = PaketLangganan::factory()->create(['is_active' => false]);
    $pengguna = User::factory()->create([
        'role' => 'user',
        'email_verified_at' => now(),
        'paket_langganan_dipilih_id' => $nonaktif->id,
    ]);

    Livewire::actingAs($pengguna)
        ->test(Onboarding::class)
        ->fillForm([
            'nama_buku' => 'Kas Tanpa Paket',
            'description' => 'Kas untuk uji tanpa paket',
            'saldo_awal' => 10000,
            'kategori_pemasukan' => [['nama' => 'Gaji Polos'], ['nama' => 'Bonus Polos']],
            'kategori_pengeluaran' => [['nama' => 'Makan Polos'], ['nama' => 'Transport Polos']],
        ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(filament()->getUrl());
})->group('langganan', 'beli-paket');

test('halaman langganan membuka modal otomatis dan membersihkan kolom', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    MetodePembayaran::factory()->create(['is_active' => true]);
    $pengguna = buatPenggunaSelesaiOnboarding();
    $pengguna->forceFill(['paket_langganan_dipilih_id' => $paket->id])->save();

    // Pilihan dari query langsung membuka modal.
    Livewire::withQueryParams(['paket' => $paket->id])
        ->actingAs($pengguna)
        ->test(PilihPaketLangganan::class)
        ->assertSuccessful()
        ->assertSet('modalTerbuka', true)
        ->assertSet('paketId', $paket->id);

    expect($pengguna->fresh()->paket_langganan_dipilih_id)->toBeNull();

    // Pilihan dari kolom tetap membuka modal sekali saat kembali tanpa query.
    $pengguna->forceFill(['paket_langganan_dipilih_id' => $paket->id])->save();

    Livewire::actingAs($pengguna)
        ->test(PilihPaketLangganan::class)
        ->assertSuccessful()
        ->assertSet('modalTerbuka', true)
        ->assertSet('paketId', $paket->id);

    expect($pengguna->fresh()->paket_langganan_dipilih_id)->toBeNull();
})->group('langganan', 'beli-paket');

test('halaman langganan dengan paket tidak valid tidak membuka modal', function () {
    bersihkanDataBeliPaket();
    $nonaktif = PaketLangganan::factory()->create(['is_active' => false]);
    MetodePembayaran::factory()->create(['is_active' => true]);
    $pengguna = buatPenggunaSelesaiOnboarding();
    $pengguna->forceFill(['paket_langganan_dipilih_id' => $nonaktif->id])->save();

    Livewire::withQueryParams(['paket' => $nonaktif->id])
        ->actingAs($pengguna)
        ->test(PilihPaketLangganan::class)
        ->assertSuccessful()
        ->assertSet('modalTerbuka', false)
        ->assertSet('paketId', null);

    // Pilihan basi ikut dibersihkan agar tidak muncul berulang.
    expect($pengguna->fresh()->paket_langganan_dipilih_id)->toBeNull();
})->group('langganan', 'beli-paket');

test('order dari modal beli-paket memakai total hitungan server', function () {
    bersihkanDataBeliPaket();
    $pengguna = buatPenggunaSelesaiOnboarding();
    $paket = PaketLangganan::factory()->create(['label' => 'Paket Beli Server', 'harga' => 100000, 'durasi_hari' => 30, 'is_active' => true]);
    $metode = MetodePembayaran::factory()->create(['is_active' => true]);
    $voucher = Voucher::factory()->create(['jumlah_diskon' => 20, 'masa_aktif' => today()->addMonth(), 'dapat_dipakai_berulang' => true]);
    $kode = VoucherCode::factory()->create(['voucher_id' => $voucher->id, 'code' => 'BELI20UJI']);

    Livewire::withQueryParams(['paket' => $paket->id])
        ->actingAs($pengguna)
        ->test(PilihPaketLangganan::class)
        ->set('kodeVoucher', $kode->code)
        ->call('terapkanVoucher')
        ->set('voucherPersen', 100)
        ->call('buatOrder')
        ->assertSuccessful()
        ->assertHasNoErrors();

    $order = Langganan::query()->where('user_id', $pengguna->id)->latest('id')->firstOrFail();

    expect($order->harga)->toBe(100000)
        ->and($order->persentase_diskon)->toBe(20)
        ->and($order->nominal_diskon)->toBe(20000)
        ->and($order->total_pembayaran)->toBe(80000)
        ->and($order->label_metode_pembayaran)->toBe($metode->label);
})->group('langganan', 'beli-paket');

test('landing menampilkan tombol beli dengan label sesuai status login', function () {
    bersihkanDataBeliPaket();
    $paket = PaketLangganan::factory()->create(['label' => 'Landing Beli Uji', 'harga' => 20000, 'durasi_hari' => 30, 'is_active' => true]);
    $tautan = route('beli-paket', $paket);

    $htmlTamu = $this->get('/')->assertOk()->getContent();

    expect($htmlTamu)->toContain($tautan)
        ->and($htmlTamu)->toContain('Daftar untuk membeli');

    $pengguna = buatPenggunaSelesaiOnboarding();
    $htmlMasuk = $this->actingAs($pengguna)->get('/')->assertOk()->getContent();

    expect($htmlMasuk)->toContain($tautan)
        ->and($htmlMasuk)->toContain('Beli paket');
})->group('langganan', 'beli-paket');

test('kolom paket dipilih terdaftar dan terhapus null saat paket dihapus', function () {
    bersihkanDataBeliPaket();

    expect(Schema::hasColumn('users', 'paket_langganan_dipilih_id'))->toBeTrue();

    $paket = PaketLangganan::factory()->create(['is_active' => true]);
    $pengguna = User::factory()->create(['role' => 'user', 'paket_langganan_dipilih_id' => $paket->id]);

    $paket->delete();

    expect($pengguna->fresh()->paket_langganan_dipilih_id)->toBeNull();
})->group('langganan', 'beli-paket');
