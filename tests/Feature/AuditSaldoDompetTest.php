<?php

use App\Filament\Resources\AuditSaldoDompetResource;
use App\Filament\Resources\AuditSaldoDompetResource\Pages\ListAuditSaldoDompet;
use App\Filament\Resources\SumberDanaResource\Pages\ListSumberDana;
use App\Models\AuditSaldoDompet;
use App\Models\BukuKas;
use App\Models\Kategori;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AuditSaldoDompetService;
use App\Services\TransaksiService;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
    $tunai = SumberDana::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Tunai',
        'jenis' => 'tunai',
        'saldo' => 500000,
        'is_default' => true,
    ]);
    $bank = SumberDana::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Bank',
        'jenis' => 'rekening',
        'saldo' => 500000,
    ]);
    foreach ([$tunai, $bank] as $sumberDana) {
        Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
            'user_id' => $user->id,
            'buku_kas_id' => $bukuKas->id,
            'sumber_dana_id' => $sumberDana->id,
            'nominal' => 1,
            'jenis' => 'Pemasukan',
            'tanggal' => now(),
        ]));
    }

    return compact('user', 'bukuKas', 'tunai', 'bank');
}

test('audit saldo tidak terdaftar di navigasi untuk semua peran', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    expect(AuditSaldoDompetResource::shouldRegisterNavigation())->toBeFalse();
})->with(['reguler', 'premium', 'admin']);

test('audit saldo membuat transaksi penyesuaian per sumber dana dan menyimpan snapshot', function () {
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
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->whereNotNull('kategori_id')->count())->toBe(0)
        ->and(Kategori::withoutGlobalScopes()->where('nama', 'Audit Saldo')->count())->toBe(0)
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->get()
            ->map(fn (Transaksi $transaksi): string => $transaksi->namaKategoriLaporan())->unique()->all())->toBe(['Audit Saldo']);
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

test('audit menolak sumber dana milik pengguna lain', function () {
    ['user' => $user, 'bukuKas' => $bukuKas] = buatDataAuditSaldo();
    $userLain = User::factory()->create(['role' => 'reguler', 'masa_aktif' => today()->addMonth()]);
    $sumberDanaLain = SumberDana::withoutGlobalScopes()->create([
        'user_id' => $userLain->id,
        'nama_dompet' => 'Sumber dana lain',
        'saldo' => 1000,
        'is_default' => true,
    ]);

    expect(fn () => app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Tidak sah', [
        ['dompet_id' => $sumberDanaLain->id, 'saldo_aplikasi' => 1000, 'saldo_riil' => 2000],
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

test('pengguna dapat menjalankan audit dari halaman dompet atau daftar audit dan membuka riwayatnya', function (string $halaman) {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();

    $komponen = Livewire::actingAs($user)->test($halaman);

    if ($halaman === ListDompet::class) {
        $komponen->assertActionVisible('riwayatAudit');
    }

    $komponen
        ->assertActionVisible('auditSaldo')
        ->callAction('auditSaldo', data: [
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'catatan' => null,
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

    if ($halaman === ListAuditSaldoDompet::class) {
        $komponen->assertCanSeeTableRecords(AuditSaldoDompet::all());
    }

    Livewire::actingAs($user)
        ->test(ListAuditSaldoDompet::class)
        ->assertCanSeeTableRecords(AuditSaldoDompet::all());

    expect(AuditSaldoDompet::firstOrFail()->catatan)->toBeNull()
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->firstOrFail()->deskripsi)->toBe('Penyesuaian saldo dompet')
        ->and($tunai->fresh()->saldo)->toBe(490000)
        ->and($bukuKas->fresh()->saldo)->toBe(990000);
})->with([
    'halaman dompet' => [ListDompet::class],
    'daftar audit' => [ListAuditSaldoDompet::class],
]);

test('action audit pada record hanya mengaudit sumber dana tersebut', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->assertTableActionVisible('auditSaldo', $tunai)
        ->callTableAction('auditSaldo', $tunai, data: [
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'catatan' => 'Audit tunai saja',
            'rincian' => [[
                'dompet_id' => $tunai->id,
                'nama_dompet' => $tunai->nama_dompet,
                'saldo_aplikasi' => 500000,
                'saldo_riil' => 490000,
                'catatan' => null,
            ]],
        ])
        ->assertHasNoTableActionErrors();

    $audit = AuditSaldoDompet::firstOrFail();
    expect($audit->detail)->toHaveCount(1)
        ->and($audit->detail->first()->dompet_id)->toBe($tunai->id)
        ->and($tunai->fresh()->saldo)->toBe(490000)
        ->and($bank->fresh()->saldo)->toBe(500000);
});

test('form audit menempatkan kas tanggal dan catatan dalam grid tiga kolom serta catatan opsional', function () {
    ['user' => $user] = buatDataAuditSaldo();

    $komponen = Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->mountAction('auditSaldo');
    $namaSchema = $komponen->instance()->getMountedActionSchemaName();
    $grid = collect($komponen->instance()->{$namaSchema}->getComponents())
        ->first(fn ($component): bool => $component instanceof Grid);

    $komponen->assertFormFieldExists('catatan', fn (Textarea $field): bool => ! $field->isRequired());
    $komponen->assertFormFieldDoesNotExist('rincian.0.catatan');

    expect($grid)->toBeInstanceOf(Grid::class)
        ->and($grid->getColumns('lg'))->toBe(3)
        ->and($grid->getChildSchema()->getFlatFields())->toHaveKeys(['buku_kas_id', 'tanggal', 'catatan']);
});

test('form audit hanya menampilkan sumber dana yang memiliki transaksi pada kas terpilih', function () {
    ['user' => $user, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();
    $kasLain = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Lain',
        'saldo' => 0,
    ]);
    $sumberDanaLain = SumberDana::create([
        'user_id' => $user->id,
        'nama_dompet' => 'Sumber Dana Kas Lain',
        'saldo' => 25000,
    ]);
    Transaksi::withoutEvents(fn () => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $kasLain->id,
        'sumber_dana_id' => $sumberDanaLain->id,
        'nominal' => 1,
        'jenis' => 'Pemasukan',
        'tanggal' => now(),
    ]));

    $komponen = Livewire::actingAs($user)
        ->test(ListSumberDana::class)
        ->mountAction('auditSaldo');
    $namaSchema = $komponen->instance()->getMountedActionSchemaName();
    $rincian = $komponen->instance()->{$namaSchema}->getFlatFields(withHidden: true)['rincian'];
    expect(collect($rincian->getState())->pluck('dompet_id')->sort()->values()->all())->toBe([$tunai->id, $bank->id]);

    $komponen->set('mountedActions.0.data.buku_kas_id', $kasLain->id);

    $namaSchema = $komponen->instance()->getMountedActionSchemaName();
    $rincian = $komponen->instance()->{$namaSchema}->getFlatFields(withHidden: true)['rincian'];

    expect(collect($rincian->getState())->pluck('dompet_id')->values()->all())->toBe([$sumberDanaLain->id]);
});

test('audit menolak sumber dana yang tidak memiliki transaksi pada kas terpilih', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai] = buatDataAuditSaldo();
    $kasLain = BukuKas::create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Lain',
        'saldo' => 0,
    ]);

    expect(fn () => app(AuditSaldoDompetService::class)->simpan($user, $kasLain, now(), null, [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 450000],
    ]))->toThrow(ValidationException::class);
});

test('hasil hitung pecahan menjadi saldo riil saat audit disimpan', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai] = buatDataAuditSaldo();

    Livewire::actingAs($user)
        ->test(ListDompet::class)
        ->callAction('auditSaldo', data: [
            'buku_kas_id' => $bukuKas->id,
            'tanggal' => now(),
            'catatan' => 'Hitung uang tunai',
            'rincian' => [
                [
                    'dompet_id' => $tunai->id,
                    'nama_dompet' => $tunai->nama_dompet,
                    'saldo_aplikasi' => 500000,
                    'saldo_riil' => 500000,
                    'jumlah_pecahan' => [
                        'kertas_100000' => 2,
                        'kertas_1000' => 2,
                        'logam_1000' => 1,
                        'logam_500' => 1,
                    ],
                    'catatan' => null,
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    expect(AuditSaldoDompet::firstOrFail()->detail->firstOrFail()->saldo_riil)->toBe(203500)
        ->and($tunai->fresh()->saldo)->toBe(203500);
});

test('form audit menyembunyikan penghitung uang dari sumber dana non-Tunai', function () {
    ['user' => $user, 'bank' => $bank] = buatDataAuditSaldo();
    $bank->update(['jenis' => 'rekening']);

    $komponen = Livewire::actingAs($user)
        ->test(ListSumberDana::class)
        ->mountAction('auditSaldo');
    $namaSchema = $komponen->instance()->getMountedActionSchemaName();
    $bagianHitungUang = collect($komponen->instance()->{$namaSchema}->getFlatComponents(withActions: false, withHidden: true))
        ->filter(fn ($bagian): bool => $bagian instanceof Section && $bagian->getHeading() === 'Hitung uang kas')
        ->values();

    expect($bagianHitungUang)->toHaveCount(2)
        ->and($bagianHitungUang->map(fn (Section $bagian): bool => $bagian->isVisible())->sort()->values()->all())->toBe([false, true])
        ->and((new SumberDana(['jenis' => null]))->mendukungHitungUang())->toBeFalse();
});

test('audit menolak pecahan untuk sumber dana non-Tunai meski data dikirim langsung', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai, 'bank' => $bank] = buatDataAuditSaldo();
    $bank->update(['jenis' => 'rekening']);

    expect(fn () => app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Data tidak valid', [
        ['dompet_id' => $bank->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 500000, 'jumlah_pecahan' => ['kertas_100000' => 1]],
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 500000],
    ]))->toThrow(ValidationException::class)
        ->and(AuditSaldoDompet::count())->toBe(0)
        ->and(Transaksi::whereNotNull('audit_saldo_dompet_detail_id')->count())->toBe(0);
});

test('riwayat audit tetap menyimpan rincian pecahan lama setelah jenis sumber dana berubah', function () {
    ['user' => $user, 'bukuKas' => $bukuKas, 'tunai' => $tunai] = buatDataAuditSaldo();

    $audit = app(AuditSaldoDompetService::class)->simpan($user, $bukuKas, now(), 'Audit awal', [
        ['dompet_id' => $tunai->id, 'saldo_aplikasi' => 500000, 'saldo_riil' => 500000, 'jumlah_pecahan' => ['logam_1000' => 2]],
    ]);

    $tunai->update(['jenis' => 'rekening']);
    $audit->refresh();

    expect($audit->detail)->toHaveCount(1)
        ->and($audit->detail->first()->saldo_riil)->toBe(2000)
        ->and($tunai->fresh()->jenis)->toBe(
            \App\Enums\JenisSumberDana::Rekening,
        );
});

test('bagian uang logam pada penghitung pecahan tertutup secara default', function () {
    $html = view('filament.forms.components.hitung-uang-kas', [
        'kelompokPecahan' => [
            'Uang Kertas' => ['kertas_1000' => 1000],
            'Uang Logam' => ['logam_1000' => 1000],
        ],
        'getStatePath' => fn (): string => 'data.rincian.0.jumlah_pecahan',
    ])->render();

    expect($html)->toContain('uangLogamTerbuka: false', 'x-show="uangLogamTerbuka"', 'type="checkbox"', 'x-model="uangLogamTerbuka"', 'transform: translateX(')
        ->and($html)->toContain(
            'grid-cols-1 gap-2 p-2 sm:grid-cols-2 sm:p-3',
            'bg-white',
            'text-slate-800',
            'border-slate-200',
            'dark:bg-gray-900',
            'dark:text-gray-100',
            'grid grid-cols-1 gap-3 sm:grid-cols-2',
        )
        ->and($html)->toContain('pecahan-uang-logam', 'Tampilkan pecahan uang logam', 'Uang Logam', '<svg', 'Ulangi');
});
