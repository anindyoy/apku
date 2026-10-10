<?php

use App\Enums\StatusLangganan;
use App\Enums\StatusTrialPremium;
use App\Filament\Widgets\AdminOverview;
use App\Models\ApplicationSetting;
use App\Models\Langganan;
use App\Models\TrialPremium;
use App\Models\User;
use App\Services\BatalkanTrialPremium;
use App\Services\MulaiTrialPremium;
use App\Services\PengaturanTrial;
use App\Services\SetujuiLangganan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

// Pembuat akun baru yang memenuhi syarat trial (belum pernah masa aktif dan langganan).
if (! function_exists('buatCalonTrial')) {
    function buatCalonTrial(array $atribut = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'type' => 'reguler',
            'masa_aktif' => null,
            'email_verified_at' => now(),
            'hp' => '081234567890',
        ], $atribut));
    }
}

test('trial menolak tiap syarat kelayakan dengan pesan yang sesuai', function () {
    $layanan = app(MulaiTrialPremium::class);

    // Admin tidak boleh ikut trial.
    $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'hp' => '081000000001']);
    expect($layanan->alasanTidakLayak($admin))->toContain('admin');

    // Saklar program mati menolak akun yang sebenarnya layak.
    ApplicationSetting::updateOrCreate(['key' => 'trial'], ['value' => ['aktif' => false, 'durasi_hari' => 30]]);
    $layak = buatCalonTrial(['hp' => '081000000002', 'email' => 'layak-trial@example.com']);
    expect($layanan->alasanTidakLayak($layak))->toContain('tidak aktif');
    ApplicationSetting::where('key', 'trial')->delete();

    // Email belum terverifikasi.
    $tanpaEmail = buatCalonTrial(['hp' => '081000000003', 'email' => 'tanpa-email@example.com']);
    $tanpaEmail->forceFill(['email_verified_at' => null])->save();
    expect($layanan->alasanTidakLayak($tanpaEmail->fresh()))->toContain('Verifikasi email');

    // Nomor HP kosong.
    $tanpaHp = buatCalonTrial(['email' => 'tanpa-hp@example.com']);
    $tanpaHp->forceFill(['hp' => null])->save();
    expect($layanan->alasanTidakLayak($tanpaHp->fresh()))->toContain('nomor HP');

    // Sedang Premium aktif.
    $premium = buatCalonTrial(['hp' => '081000000004', 'email' => 'premium-aktif@example.com']);
    $premium->forceFill(['type' => 'premium', 'masa_aktif' => today()->addDays(10)->toDateString()])->save();
    expect($layanan->alasanTidakLayak($premium->fresh()))->toContain('sedang Premium');

    // Pernah punya masa aktif walau sudah kedaluwarsa.
    $bekas = buatCalonTrial(['hp' => '081000000005', 'email' => 'bekas-aktif@example.com']);
    $bekas->forceFill(['type' => 'reguler', 'masa_aktif' => today()->subDay()->toDateString()])->save();
    expect($layanan->alasanTidakLayak($bekas->fresh()))->toContain('belum pernah memiliki masa aktif');

    // Pernah berlangganan disetujui walau masa aktif dikosongkan.
    $pelanggan = buatCalonTrial(['hp' => '081000000006', 'email' => 'pelanggan@example.com']);
    Langganan::factory()->create(['user_id' => $pelanggan->getKey(), 'status' => StatusLangganan::Disetujui]);
    expect($layanan->alasanTidakLayak($pelanggan->fresh()))->toContain('belum pernah berlangganan');
})->group('trial');

test('trial mencegah pemakaian ulang akun hp dan email ternormalisasi', function () {
    $layanan = app(MulaiTrialPremium::class);
    $pertama = buatCalonTrial(['hp' => '0812-3456-7890', 'email' => 'Test.User+promo@Gmail.com']);
    $layanan->handle($pertama);

    // Akun sama tidak bisa mengulang.
    $pertama->refresh();
    $pertama->forceFill(['type' => 'reguler', 'masa_aktif' => null])->save();
    expect($layanan->alasanTidakLayak($pertama->fresh()))->toContain('sudah pernah');

    // HP sama dengan format berbeda ditolak.
    $hpSama = buatCalonTrial(['hp' => '+62812-3456-7890', 'email' => 'orang-lain@example.com']);
    expect($layanan->alasanTidakLayak($hpSama))->toContain('Nomor HP');

    // Email Gmail sama dengan titik dan alias berbeda ditolak.
    $emailSama = buatCalonTrial(['hp' => '081999000111', 'email' => 'test.user@gmail.com']);
    expect($layanan->alasanTidakLayak($emailSama))->toContain('Email');

    // Klik dua kali hanya membuat satu trial.
    $ganda = buatCalonTrial(['hp' => '081999000222', 'email' => 'klik-ganda@example.com']);
    $layanan->handle($ganda);
    expect(fn () => $layanan->handle($ganda->fresh()))->toThrow(ValidationException::class);
    expect(TrialPremium::query()->where('user_id', $ganda->getKey())->count())->toBe(1);
})->group('trial');

test('trial aktivasi mengatur masa aktif dan membuka fitur premium', function () {
    Notification::fake();
    $user = buatCalonTrial(['hp' => '081777000111', 'email' => 'aktivasi@example.com']);

    $trial = app(MulaiTrialPremium::class)->handle($user);

    $berakhir = today()->copy()->addDays(29)->toDateString();
    expect($trial->status)->toBe(StatusTrialPremium::Aktif)
        ->and($trial->mulai_pada->toDateString())->toBe(today()->toDateString())
        ->and($trial->berakhir_pada->toDateString())->toBe($berakhir)
        ->and($user->fresh()->type)->toBe('premium')
        ->and($user->fresh()->masa_aktif->toDateString())->toBe($berakhir)
        ->and($user->fresh()->masaAktifBerlaku())->toBeTrue()
        // Kuota Reguler 2 kas dan 2 dompet terbuka selama trial.
        ->and($user->fresh()->dapatMembuatBukuKas())->toBeTrue()
        ->and($user->fresh()->dapatMembuatSumberDana())->toBeTrue();

    Notification::assertSentTo($user, \App\Notifications\TrialPremiumAktif::class);
})->group('trial');

test('trial penutupan mengubah status akses reguler tanpa menghapus data', function () {
    $user = buatCalonTrial(['hp' => '081777000222', 'email' => 'tutup@example.com']);
    $trial = app(MulaiTrialPremium::class)->handle($user);
    $kunci = $trial->getKey();

    // Geser tanggal berakhir ke kemarin untuk simulasi kedaluwarsa.
    $kemarin = today()->copy()->subDay()->toDateString();
    $trial->forceFill(['berakhir_pada' => $kemarin])->save();
    $user->forceFill(['masa_aktif' => $kemarin])->save();

    $this->artisan('trial-premium:proses')->assertSuccessful();

    expect($trial->fresh()->status)->toBe(StatusTrialPremium::Berakhir)
        ->and($user->fresh()->type)->toBe('reguler')
        ->and(TrialPremium::query()->whereKey($kunci)->exists())->toBeTrue()
        ->and($user->fresh()->notifications()->where('data->reminder_key', 'trial-premium:'.$kunci.':berakhir')->count())->toBe(1);
})->group('trial');

test('trial konversi menambah sisa ke masa aktif berbayar', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = buatCalonTrial(['hp' => '081777000333', 'email' => 'konversi@example.com']);
    $trial = app(MulaiTrialPremium::class)->handle($user);
    $akhirTrial = $trial->berakhir_pada->toDateString();

    $order = Langganan::factory()->create([
        'user_id' => $user->getKey(),
        'status' => StatusLangganan::MenungguVerifikasi,
        'durasi_hari' => 30,
        'bukti_pembayaran_path' => 'bukti/file.jpg',
    ]);

    $hasil = app(SetujuiLangganan::class)->handle($admin, $order);

    // Mulai sehari setelah sisa trial, lalu tambah 30 hari inklusif.
    $mulai = today()->copy()->addDays(30)->toDateString();
    $sampai = today()->copy()->addDays(59)->toDateString();
    expect($hasil->masa_aktif_mulai->toDateString())->toBe($mulai)
        ->and($hasil->masa_aktif_sampai->toDateString())->toBe($sampai)
        ->and($user->fresh()->masa_aktif->toDateString())->toBe($sampai)
        ->and($trial->fresh()->status)->toBe(StatusTrialPremium::Dikonversi)
        ->and($trial->fresh()->langganan_id)->toBe($order->getKey())
        ->and($trial->fresh()->dikonversi_pada)->not->toBeNull()
        ->and($akhirTrial)->toBe(today()->copy()->addDays(29)->toDateString());
})->group('trial');

test('trial pengingat melewati h-30 bawaan dan hanya kirim h-7 serta h-1', function () {
    $user = buatCalonTrial(['hp' => '081777000444', 'email' => 'ingat@example.com']);
    $trial = app(MulaiTrialPremium::class)->handle($user);
    $kunci = $trial->getKey();

    // Paksakan tanggal agar bertepatan dengan jadwal H-30 bawaan.
    $tigaPuluh = today()->copy()->addDays(30)->toDateString();
    $trial->forceFill(['berakhir_pada' => $tigaPuluh])->save();
    $user->forceFill(['masa_aktif' => $tigaPuluh])->save();

    $this->artisan('masa-aktif:kirim-pengingat')->assertSuccessful();
    expect($user->fresh()->notifications()->where('data->reminder_key', 'like', 'masa-aktif:%')->count())->toBe(0);

    // H-7 khusus trial terkirim satu kali walau perintah dijalankan dua kali.
    $tujuh = today()->copy()->addDays(7)->toDateString();
    $trial->forceFill(['berakhir_pada' => $tujuh])->save();
    $user->forceFill(['masa_aktif' => $tujuh])->save();
    $this->artisan('trial-premium:proses')->assertSuccessful();
    $this->artisan('trial-premium:proses')->assertSuccessful();
    expect($user->fresh()->notifications()->where('data->reminder_key', 'trial-premium:'.$kunci.':H-7')->count())->toBe(1);

    // H-1 khusus trial terkirim.
    $satu = today()->copy()->addDays(1)->toDateString();
    $trial->forceFill(['berakhir_pada' => $satu])->save();
    $user->forceFill(['masa_aktif' => $satu, 'type' => 'premium'])->save();
    $this->artisan('trial-premium:proses')->assertSuccessful();
    expect($user->fresh()->notifications()->where('data->reminder_key', 'trial-premium:'.$kunci.':H-1')->count())->toBe(1);
})->group('trial');

test('trial akun dihapus tetap memblokir hp dan email yang sama', function () {
    $layanan = app(MulaiTrialPremium::class);
    $lama = buatCalonTrial(['hp' => '081777000555', 'email' => 'hapus@example.com']);
    $layanan->handle($lama);
    $lama->delete();

    // Catatan trial dipertahankan dengan user_id null.
    expect(TrialPremium::query()->whereNull('user_id')->where('hp_normal', '081777000555')->exists())->toBeTrue();

    $baru = buatCalonTrial(['hp' => '081777000555', 'email' => 'hapus@example.com']);
    expect($layanan->alasanTidakLayak($baru))->not->toBeNull();
    expect(fn () => $layanan->handle($baru))->toThrow(ValidationException::class);
})->group('trial');

test('trial statistik admin dan pembatalan berjalan benar', function () {
    Cache::flush();
    $admin = User::factory()->create(['role' => 'admin']);

    $aktif = buatCalonTrial(['hp' => '081888000001', 'email' => 'stat-aktif@example.com']);
    app(MulaiTrialPremium::class)->handle($aktif);

    $konversi = buatCalonTrial(['hp' => '081888000002', 'email' => 'stat-konversi@example.com']);
    app(MulaiTrialPremium::class)->handle($konversi);
    $order = Langganan::factory()->create([
        'user_id' => $konversi->getKey(),
        'status' => StatusLangganan::MenungguVerifikasi,
        'durasi_hari' => 30,
        'bukti_pembayaran_path' => 'bukti/file.jpg',
    ]);
    Notification::fake();
    app(SetujuiLangganan::class)->handle($admin, $order);

    $berakhir = buatCalonTrial(['hp' => '081888000003', 'email' => 'stat-berakhir@example.com']);
    $trialBerakhir = app(MulaiTrialPremium::class)->handle($berakhir);
    $trialBerakhir->forceFill(['berakhir_pada' => today()->copy()->subDay()->toDateString()])->save();
    $berakhir->forceFill(['masa_aktif' => today()->copy()->subDay()->toDateString()])->save();
    $this->artisan('trial-premium:proses')->assertSuccessful();

    $total = TrialPremium::query()->count();
    $jumlahAktif = TrialPremium::query()->where('status', StatusTrialPremium::Aktif)->count();
    $jumlahKonversi = TrialPremium::query()->where('status', StatusTrialPremium::Dikonversi)->count();
    $mingguIni = TrialPremium::query()
        ->where('status', StatusTrialPremium::Berakhir)
        ->whereDate('updated_at', '>=', today()->copy()->subDays(7))
        ->count();
    expect($total)->toBe(3)
        ->and($jumlahAktif)->toBe(1)
        ->and($jumlahKonversi)->toBe(1)
        ->and($mingguIni)->toBe(1);

    Cache::flush();
    $komponen = Livewire::actingAs($admin)->test(AdminOverview::class)->assertSuccessful();
    $html = $komponen->html();
    expect($html)->toContain('Trial aktif', 'Total trial', 'Konversi');

    // Pembatalan admin mencabut akses premium dari trial yang masih aktif.
    $targetBatal = $aktif->fresh()->trialPremium;
    $batal = app(BatalkanTrialPremium::class)->handle($admin, $targetBatal);
    expect($batal->status)->toBe(StatusTrialPremium::Berakhir);
    expect($aktif->fresh()->type)->toBe('reguler');

    // Pengaturan trial hanya bisa disimpan admin dengan batas 1 sampai 90.
    $layananAtur = app(PengaturanTrial::class);
    expect(fn () => $layananAtur->simpan($aktif->fresh(), ['aktif' => true, 'durasi_hari' => 30]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $layananAtur->simpan($admin, ['aktif' => true, 'durasi_hari' => 15]);
    expect($layananAtur->durasiHari())->toBe(15);
})->group('trial');
