<?php

use App\Filament\Resources\AuditSaldoDompetResource\Pages\ListAuditSaldoDompet;
use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Models\AuditSaldoDompet;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AuditSaldoDompetService;
use App\Services\TransaksiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function buatDataAuditSaldo(): array
{
    $user = User::factory()->create([
        'role' => 'reguler',
        'masa_aktif' => today()->addMonth(),
    ]);
    $bukuKas = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Utama',
        'saldo' => 1000000,
        'is_default' => true,
    ]);
    $tunai = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Tunai',
        'saldo' => 500000,
        'is_default' => true,
    ]);
    $bank = Dompet::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'saldo' => 500000,
    ]);

    return compact('user', 'bukuKas', 'tunai', 'bank');
}

test('audit saldo membuat transaksi penyesuaian per dompet dan menyimpan snapshot', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();

    $audit = app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Rekonsiliasi bulanan', [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 450000],
        ['dompet_id' => $bank->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 600000, 'catatan' => 'Bunga bank'],
    ]);

    expect($audit->total_saldo_aplikasi)->toBe(1000000)
        ->and($audit->total_saldo_riil)->toBe(1050000)
        ->and($audit->total_selisih)->toBe(50000)
        ->and($audit->detail)->toHaveCount(2)
        ->and($tunai->fresh()->saldo)->toBe(450000)
        ->and($bank->fresh()->saldo)->toBe(600000)
        ->and($bukuKas->fresh()->saldo)->toBe(1050000)
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->count())->toBe(2)
        ->and(JenisTransaksi::where('nama_jenis', 'Audit Saldo')->where('is_system', true)->count())->toBe(2);
});

test('audit tanpa selisih tetap menyimpan snapshot tanpa transaksi', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();

    $audit = app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Saldo sesuai', [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 500000],
        ['dompet_id' => $bank->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 500000],
    ]);

    expect($audit->detail)->toHaveCount(2)
        ->and($audit->total_selisih)->toBe(0)
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->count())->toBe(0);
});

test('audit menolak snapshot saat saldo aplikasi telah berubah', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai] = buatDataAuditSaldo();
    $tunai->update(['saldo' => 525000]);

    expect(fn () => app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Data lama', [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 450000],
    ]))->toThrow(ValidationException::class)
        ->and(AuditSaldoDompet::count())->toBe(0)
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->count())->toBe(0);
});

test('audit menolak dompet milik pengguna lain', function () {
    ['user' => $user, 'bukuKas' => $bukuKas] = buatDataAuditSaldo();
    $userLain = User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addMonth()]);
    $dompetLain = Dompet::withoutGlobalScopes()->create([
        'user_id' => $userLain->id,
        'nama_dompet' => 'Dompet lain',
        'saldo' => 1000,
        'is_default' => true,
    ]);

    expect(fn () => app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Tidak sah', [
        ['dompet_id' => $dompetLain->id, 'saldo_aplikasi' => 1000, 'saldo_riil' => 2000],
    ]))->toThrow(AuthorizationException::class)
        ->and(AuditSaldoDompet::count())->toBe(0);
});

test('transaksi penyesuaian audit tidak dapat diubah atau dihapus langsung', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai] = buatDataAuditSaldo();
    app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Rekonsiliasi', [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 450000],
    ]);
    $transaksi = Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->firstOrFail();

    expect(fn () => app(TransaksiService::class)->ubah($user, $transaksi, ['nominal' => 1]))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(TransaksiService::class)->hapus($user, $transaksi))
        ->toThrow(ValidationException::class);
});

test('pengguna dapat menjalankan audit dari halaman dompet dan membuka riwayatnya', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->assertActionVisible('auditSaldo')
        ->assertActionVisible('riwayatAudit')
        ->callAction('auditSaldo', data: [
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'catatan' => 'Audit dari halaman dompet',
            'rincian' => [
                [
                    'dompet_id' => $tunai->id,
                    'nama_dompet' => $tunai->nama_dompet,
                    'saldo_aplikasi' => 500000,
                    'saldo_riil' => 490000,
                    'catatan' => null,
                ],
                [
                    'dompet_id' => $bank->id,
                    'nama_dompet' => $bank->nama_dompet,
                    'saldo_aplikasi' => 500000,
                    'saldo_riil' => 500000,
                    'catatan' => null,
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    Livewire::actingAs($user)
        ->test(ListAuditSaldoDompet::class)
        ->assertCanSeeTableRecords(AuditSaldoDompet::all());

    expect($tunai->fresh()->saldo)->toBe(490000)
        ->and($bukuKas->fresh()->saldo)->toBe(990000);
});
