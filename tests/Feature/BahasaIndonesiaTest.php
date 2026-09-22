<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Validator;

test('email verifikasi dan reset kata sandi menggunakan bahasa Indonesia', function () {
    VerifyEmail::createUrlUsing(fn () => 'https://example.com/verifikasi');
    ResetPassword::createUrlUsing(fn () => 'https://example.com/atur-ulang');
    $user = User::factory()->make(['email' => 'pengguna@example.com']);

    $verification = (new VerifyEmail)->toMail($user);
    $reset = (new ResetPassword('token-pengujian'))->toMail($user);

    expect($verification->subject)->toBe('Verifikasi alamat email Anda')
        ->and($verification->actionText)->toBe('Verifikasi Alamat Email')
        ->and($verification->introLines[0])->toContain('Pilih tombol')
        ->and($verification->outroLines[0])->toContain('Jika Anda tidak membuat akun')
        ->and($reset->subject)->toBe('Setel ulang kata sandi Anda')
        ->and($reset->actionText)->toBe('Atur Ulang Kata Sandi')
        ->and($reset->introLines[0])->toContain('Anda menerima email ini')
        ->and($reset->outroLines[0])->toContain('kedaluwarsa')
        ->and($reset->outroLines[1])->toContain('Jika Anda tidak meminta');
});

test('pesan validasi dan autentikasi menggunakan bahasa Indonesia', function () {
    $errors = Validator::make(['email' => 'salah', 'password' => 'abc'], [
        'email' => ['required', 'email'],
        'password' => ['required', 'min:8'],
        'name' => ['required'],
    ])->errors();

    expect($errors->first('email'))->toContain('Alamat email')->toContain('valid')
        ->and($errors->first('password'))->toContain('Kata sandi')->toContain('8')
        ->and($errors->first('name'))->toContain('Nama')->toContain('wajib diisi')
        ->and(__('auth.failed'))->toBe('Identitas tersebut tidak cocok dengan data kami.')
        ->and(__('passwords.sent'))->toContain('Kami telah mengirim tautan');
});

test('seluruh pesan validasi Laravel memiliki terjemahan Indonesia', function () {
    $source = require lang_path('en/validation.php');
    $translated = require lang_path('id/validation.php');

    expect(array_diff(array_keys($source), array_keys($translated)))->toBe([]);

    foreach ($source as $key => $value) {
        if (is_array($value) && $key !== 'custom' && $key !== 'attributes') {
            expect(array_diff(array_keys($value), array_keys($translated[$key])))->toBe([]);
        }
    }
});
