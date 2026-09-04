<?php

use App\Models\User;
use App\Notifications\PengingatPerpanjanganMasaAktif;
use Illuminate\Support\Carbon;

it('mengirim pengingat H-30 dan H-7 hanya satu kali', function () {
    Carbon::setTestNow('2026-09-01 08:00:00');

    $userH30 = User::factory()->create([
        'role' => 'user',
        'masa_aktif' => today()->addDays(30),
    ]);
    $userH7 = User::factory()->create([
        'role' => 'user',
        'masa_aktif' => today()->addDays(7),
    ]);
    $userLain = User::factory()->create([
        'role' => 'user',
        'masa_aktif' => today()->addDays(8),
    ]);

    $this->artisan('masa-aktif:kirim-pengingat')->assertSuccessful();
    $this->artisan('masa-aktif:kirim-pengingat')->assertSuccessful();

    expect($userH30->notifications()->count())->toBe(1)
        ->and($userH30->notifications()->first()->data['reminder_key'])
        ->toBe('masa-aktif:2026-10-01:H-30')
        ->and($userH7->notifications()->count())->toBe(1)
        ->and($userH7->notifications()->first()->data['reminder_key'])
        ->toBe('masa-aktif:2026-09-08:H-7')
        ->and($userLain->notifications()->count())->toBe(0);
});

it('tidak mengirim pengingat kepada admin', function () {
    Carbon::setTestNow('2026-09-01 08:00:00');

    $admin = User::factory()->create([
        'role' => 'admin',
        'masa_aktif' => today()->addDays(7),
    ]);

    $this->artisan('masa-aktif:kirim-pengingat')->assertSuccessful();

    expect($admin->notifications()->count())->toBe(0);
});

it('membentuk data notifikasi yang dapat ditampilkan Filament', function () {
    $pengingat = new PengingatPerpanjanganMasaAktif(7, '2026-09-08');
    $data = $pengingat->toDatabase(new stdClass);

    expect($data['format'])->toBe('filament')
        ->and($data['status'])->toBe('warning')
        ->and($data['body'])->toContain('7 hari')
        ->and($data['body'])->toContain('2026-09-08');
});
