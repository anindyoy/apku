<?php

use App\Enums\StatusLangganan;
use App\Models\ApplicationSetting;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherCode;
use App\Notifications\InvoiceOrderNotification;
use App\Notifications\PembayaranDisetujuiNotification;
use App\Notifications\PembayaranDitolakNotification;
use App\Services\BuatOrderLangganan;
use App\Services\PengaturanKontak;
use App\Services\SetujuiLangganan;
use App\Services\TolakLangganan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

test('order dibuat oleh user non-admin mengirim InvoiceOrderNotification dan tidak untuk admin', function () {
    Notification::fake();

    $paket = PaketLangganan::factory()->create([
        'label' => 'Premium Bulanan',
        'harga' => 50000,
        'durasi_hari' => 30,
        'is_active' => true,
    ]);

    $metode = MetodePembayaran::factory()->create([
        'label' => 'BCA Transfer',
        'nama_penyedia' => 'Bank BCA',
        'nomor_tujuan' => '1234567890',
        'nama_pemilik' => 'PT APKu Solusi Finansial',
        'instruksi' => 'Transfer sesuai total tagihan.',
        'is_active' => true,
    ]);

    $user = User::factory()->create(['role' => 'user', 'email' => 'user@example.com']);
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);

    $buatOrder = app(BuatOrderLangganan::class);

    // User non-admin membuat order
    $orderUser = $buatOrder->handle($user, $paket, $metode);
    Notification::assertSentTo($user, InvoiceOrderNotification::class, function ($notif) use ($orderUser) {
        return $notif->langganan->id === $orderUser->id;
    });

    // Admin membuat order tidak mengirim notifikasi invoice
    Notification::fake();
    $buatOrder->handle($admin, $paket, $metode);
    Notification::assertNothingSent();
});

test('invoice email memuat snapshot paket voucher detail pembayaran dan format nominal', function () {
    $user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);

    $voucher = Voucher::factory()->create([
        'label' => 'DISKON10',
        'jumlah_diskon' => 10,
        'masa_aktif' => now()->addDays(7),
        'dapat_dipakai_berulang' => true,
    ]);
    $voucherCode = $voucher->codes()->first();

    $paket = PaketLangganan::factory()->create([
        'label' => 'Paket Premium 3 Bulan',
        'harga' => 100000,
        'durasi_hari' => 90,
        'is_active' => true,
    ]);

    $metode = MetodePembayaran::factory()->create([
        'label' => 'Mandiri Transfer',
        'nama_penyedia' => 'Bank Mandiri',
        'nomor_tujuan' => '9876543210',
        'nama_pemilik' => 'PT APKu Solusi',
        'instruksi' => 'Pastikan nominal tepat.',
        'is_active' => true,
    ]);

    $order = app(BuatOrderLangganan::class)->handle($user, $paket, $metode, $voucherCode);

    $notif = new InvoiceOrderNotification($order);

    // Periksa konfigurasi antrean dan retry
    expect($notif->queue)->toBe('email')
        ->and($notif->afterCommit)->toBeTrue()
        ->and($notif->tries)->toBe(3)
        ->and($notif->backoff)->toBe([60, 300, 900]);

    // Render email HTML dan text
    $mail = $notif->toMail($user);
    $htmlView = is_array($mail->view) ? $mail->view['html'] : $mail->view;
    $textView = is_array($mail->view) ? ($mail->view['text'] ?? $mail->textView) : $mail->textView;
    $html = view($htmlView, $mail->viewData)->render();
    $text = view($textView, $mail->viewData)->render();

    // Verifikasi isi HTML
    expect($html)->toContain($order->kode_order)
        ->and($html)->toContain('Paket Premium 3 Bulan')
        ->and($html)->toContain('9876543210')
        ->and($html)->toContain('Bank Mandiri')
        ->and($html)->toContain('PT APKu Solusi')
        ->and($html)->toContain('Rp 90.000') // 100.000 - 10.000
        ->and($html)->toContain('DISKON10')
        ->and($html)->toContain(url('/admin/langganans'));

    // Verifikasi isi Plain Text
    expect($text)->toContain($order->kode_order)
        ->and($text)->toContain('Paket Premium 3 Bulan')
        ->and($text)->toContain('9876543210')
        ->and($text)->toContain('Rp 90.000')
        ->and($text)->toContain(url('/admin/langganans'));
});

test('setujui pembayaran mengantrekan PembayaranDisetujuiNotification dan setujui ulang tidak dobel', function () {
    Notification::fake();

    $user = User::factory()->create(['type' => 'reguler', 'masa_aktif' => null, 'email' => 'user@example.com']);
    $admin = User::factory()->create(['role' => 'admin']);

    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
        'label_paket' => 'Premium Tahunan',
        'durasi_hari' => 365,
        'total_pembayaran' => 250000,
    ]);

    $service = app(SetujuiLangganan::class);

    // Setujui pembayaran pertama kali
    $hasil = $service->handle($admin, $order, 'Pembayaran diverifikasi valid');
    expect($hasil->status)->toBe(StatusLangganan::Disetujui)
        ->and($hasil->masa_aktif_mulai)->not->toBeNull()
        ->and($hasil->masa_aktif_sampai)->not->toBeNull();

    Notification::assertSentTo($user, PembayaranDisetujuiNotification::class, function ($notif) use ($hasil) {
        return $notif->langganan->id === $hasil->id
            && $notif->queue === 'email'
            && $notif->afterCommit === true
            && $notif->tries === 3
            && $notif->backoff === [60, 300, 900];
    });

    // Render mail pesan disetujui
    $mail = (new PembayaranDisetujuiNotification($hasil))->toMail($user);
    $htmlView = is_array($mail->view) ? $mail->view['html'] : $mail->view;
    $textView = is_array($mail->view) ? ($mail->view['text'] ?? $mail->textView) : $mail->textView;
    $html = view($htmlView, $mail->viewData)->render();
    $text = view($textView, $mail->viewData)->render();

    expect($html)->toContain($hasil->kode_order)
        ->and($html)->toContain('Premium Tahunan')
        ->and($html)->toContain('Rp 250.000')
        ->and($html)->toContain(url('/admin'));

    expect($text)->toContain($hasil->kode_order)
        ->and($text)->toContain('Premium Tahunan')
        ->and($text)->toContain(url('/admin'));

    // Setujui ulang order yang sudah disetujui tidak mengirim notifikasi kedua
    Notification::fake();
    $service->handle($admin, $hasil);
    Notification::assertNothingSent();
});

test('tolak pembayaran mengantrekan PembayaranDitolakNotification beserta alasan penolakan dan wa admin', function () {
    Notification::fake();

    ApplicationSetting::updateOrCreate(
        ['key' => PengaturanKontak::KUNCI],
        ['value' => ['wa' => '6281234567890']]
    );

    $user = User::factory()->create(['email' => 'user@example.com']);
    $admin = User::factory()->create(['role' => 'admin']);

    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
        'label_paket' => 'Premium Bulanan',
        'durasi_hari' => 30,
        'total_pembayaran' => 50000,
    ]);

    $alasan = 'Struk bukti transfer blur dan tidak terbaca.';
    $hasil = app(TolakLangganan::class)->handle($admin, $order, $alasan);

    expect($hasil->status)->toBe(StatusLangganan::Ditolak)
        ->and($hasil->catatan_admin)->toBe($alasan);

    Notification::assertSentTo($user, PembayaranDitolakNotification::class, function ($notif) use ($hasil, $alasan) {
        return $notif->langganan->id === $hasil->id
            && $notif->langganan->catatan_admin === $alasan
            && $notif->queue === 'email'
            && $notif->afterCommit === true
            && $notif->tries === 3
            && $notif->backoff === [60, 300, 900];
    });

    // Render mail pesan ditolak
    $mail = (new PembayaranDitolakNotification($hasil))->toMail($user);
    $htmlView = is_array($mail->view) ? $mail->view['html'] : $mail->view;
    $textView = is_array($mail->view) ? ($mail->view['text'] ?? $mail->textView) : $mail->textView;
    $html = view($htmlView, $mail->viewData)->render();
    $text = view($textView, $mail->viewData)->render();

    expect($html)->toContain($hasil->kode_order)
        ->and($html)->toContain($alasan)
        ->and($html)->toContain('https://wa.me/6281234567890')
        ->and($html)->toContain(url('/admin/langganans'));

    expect($text)->toContain($hasil->kode_order)
        ->and($text)->toContain($alasan)
        ->and($text)->toContain('https://wa.me/6281234567890');
});

test('user admin hanya menerima channel database dan tidak menerima mail', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
    $order = Langganan::factory()->create(['user_id' => $admin->id]);

    $notifInvoice = new InvoiceOrderNotification($order);
    expect($notifInvoice->via($admin))->toBe([]);

    $notifDisetujui = new PembayaranDisetujuiNotification($order);
    expect($notifDisetujui->via($admin))->toBe(['database']);

    $notifDitolak = new PembayaranDitolakNotification($order);
    expect($notifDitolak->via($admin))->toBe(['database']);

    $regularUser = User::factory()->create(['role' => 'user', 'email' => 'user@example.com']);
    expect($notifInvoice->via($regularUser))->toBe(['mail'])
        ->and($notifDisetujui->via($regularUser))->toBe(['database', 'mail'])
        ->and($notifDitolak->via($regularUser))->toBe(['database', 'mail']);
});

test('failed memanggil Telegram tanpa membocorkan isi email atau data pembayaran', function () {
    config()->set('services.telegram.bot_token', 'test-bot-token');
    config()->set('services.telegram.chat_id', '-100999888');

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $order = Langganan::factory()->create([
        'kode_order' => 'ORD-GAGAL-01',
        'total_pembayaran' => 999999,
        'detail_pembayaran' => ['nomor_tujuan' => 'SECRET-ACC-999'],
    ]);

    $notif = new InvoiceOrderNotification($order);
    $exception = new RuntimeException('Koneksi SMTP timeout setelah 30 detik');

    $notif->failed($exception);

    Http::assertSent(function ($request) {
        $text = (string) $request['text'];

        $memuatKodeDanError = str_contains($text, 'Invoice Order')
            && str_contains($text, 'ORD-GAGAL-01')
            && str_contains($text, 'Koneksi SMTP timeout setelah 30 detik');

        // Pastikan tidak ada data detail sensitif atau nominal pembayaran
        $tidakMembocorkanData = ! str_contains($text, '999999')
            && ! str_contains($text, 'SECRET-ACC-999');

        return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request['chat_id'] === '-100999888'
            && $memuatKodeDanError
            && $tidakMembocorkanData;
    });
});
