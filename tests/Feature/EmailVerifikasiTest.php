<?php

use App\Models\User;
use Filament\Auth\Notifications\VerifyEmail as FilamentVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('email verifikasi pendaftaran memiliki subjek, sapaan nama pengguna, dan tombol aksi', function () {
    $user = User::factory()->make([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);

    $url = 'https://apku.test/admin/email-verification/verify/1/test-hash';

    $notification = new FilamentVerifyEmail;
    $notification->url = $url;

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Verifikasi alamat email Anda')
        ->and($mail->greeting)->toBe('Halo, Budi Santoso!')
        ->and($mail->actionText)->toBe('Verifikasi Alamat Email')
        ->and($mail->actionUrl)->toBe($url)
        ->and($mail->introLines[0])->toContain('Pilih tombol di bawah')
        ->and($mail->outroLines[0])->toContain('Jika Anda tidak membuat akun');
});

test('email verifikasi me-render template HTML dengan logo aplikasi dan styling APKu', function () {
    $user = User::factory()->make([
        'name' => 'Siti Rahma',
        'email' => 'siti@example.com',
    ]);

    $url = 'https://apku.test/admin/email-verification/verify/2/token-verifikasi';

    $notification = new FilamentVerifyEmail;
    $notification->url = $url;

    $mail = $notification->toMail($user);
    $html = $mail->render();

    // Memastikan logo aplikasi APKu ditampilkan dalam email
    expect($html)->toContain('logo-options/apku-dompet-wordmark.png')
        // Memastikan nama penerima, judul, dan tombol verifikasi ada di dalam HTML
        ->and($html)->toContain('Siti Rahma')
        ->and($html)->toContain('Konfirmasi Alamat Email Anda')
        ->and($html)->toContain('Verifikasi Alamat Email')
        ->and($html)->toContain($url)
        // Memastikan catatan masa berlaku tautan dan panduan keamanan ada
        ->and($html)->toContain('Catatan Keamanan')
        ->and($html)->toContain('60 menit')
        // Memastikan identitas dan tagline footer APKu ada
        ->and($html)->toContain('Catat uang, pahami keuangan')
        ->and($html)->toContain('Tutorial Penggunaan');
});

test('email verifikasi menyediakan versi teks polos alternatif', function () {
    $user = User::factory()->make([
        'name' => 'Ahmad Fauzi',
        'email' => 'ahmad@example.com',
    ]);

    $url = 'https://apku.test/admin/email-verification/verify/3/hash-plain';

    $textView = view('emails.verifikasi-email-text', [
        'url' => $url,
        'user' => $user,
        'nama' => $user->name,
    ])->render();

    expect($textView)->toContain('Halo, Ahmad Fauzi!')
        ->and($textView)->toContain($url)
        ->and($textView)->toContain('60 menit')
        ->and($textView)->toContain('APKu — Catat uang, pahami keuangan');
});

test('model User mengirim notifikasi verifikasi email menggunakan panel Filament', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'name' => 'Dewi Lestari',
        'email' => 'dewi@example.com',
    ]);

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, FilamentVerifyEmail::class, function (FilamentVerifyEmail $notification) use ($user) {
        return str_contains($notification->url, 'admin/email-verification/verify/' . $user->id);
    });
});
