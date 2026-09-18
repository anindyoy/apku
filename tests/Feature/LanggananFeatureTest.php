<?php

use App\Enums\StatusLangganan;
use App\Filament\Resources\LanggananResource\Pages\ListLangganans;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\User;
use App\Services\BuatOrderLangganan;
use App\Services\KonfirmasiPembayaranLangganan;
use App\Services\SetujuiLangganan;
use App\Services\TolakLangganan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('kartu langganan mempertahankan rincian pencarian filter dan aksi', function (string $role, bool $denganVoucher) {
    $user = User::factory()->create(['role' => $role]);
    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'kode_voucher' => $denganVoucher ? 'HEMAT' : null,
        'nominal_diskon' => $denganVoucher ? 5000 : 0,
        'total_pembayaran' => $denganVoucher ? 20000 : 25000,
        'masa_aktif_sampai' => $denganVoucher ? '2026-10-14' : null,
        'catatan_admin' => $denganVoucher ? 'Pembayaran sudah diperiksa' : null,
    ]);

    $component = Livewire::actingAs($user)->test(ListLangganans::class)
        ->assertSuccessful()->searchTable($order->kode_order)->assertCanSeeTableRecords([$order]);
    $columns = $component->instance()->getTable()->getVisibleColumns();
    expect(array_keys($columns))->toBe($role === 'admin'
        ? ['kode_order', 'user.name', 'label_paket', 'total_pembayaran', 'label_metode_pembayaran', 'status']
        : ['kode_order', 'label_paket', 'total_pembayaran', 'label_metode_pembayaran', 'status']);

    $document = new DOMDocument;
    @$document->loadHTML(mb_convert_encoding($component->html(), 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@data-subscription-card]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-subscription-card]//table')->length)->toBe(0)
        ->and($component->instance()->getTable()->getContentGrid())->toBe(['default' => 1, 'md' => 2]);
    $list = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " fi-ta-content-grid ")]')->item(0);
    expect($list)->not->toBeNull();
    expect($list->getAttribute('class'))->toContain('md:fi-grid-cols')
        ->and($list->getAttribute('style'))->toContain('--cols-md: repeat(2, minmax(0, 1fr))');
    $card = $xpath->query('//*[@data-subscription-card]')->item(0);
    expect($card->getAttribute('class'))->toContain('md:fi-grid-cols')
        ->and($card->getAttribute('style'))->toContain('--cols-md: repeat(2, minmax(0, 1fr))')
        ->and($xpath->query('./div', $card)->length)->toBe(2);
    $text = $document->textContent;
    foreach ([
        'Tanggal order: '.$order->created_at->format('d M Y H:i'),
        'Harga: Rp 25.000',
        $denganVoucher ? 'Voucher: HEMAT • Diskon: Rp 5.000' : 'Diskon: Rp 0',
        $denganVoucher ? 'Aktif sampai: 14 Oct 2026 • Catatan admin: Pembayaran sudah diperiksa' : 'Aktif sampai: -',
        'Bank Contoh • 1234567890 • APKu • Transfer sesuai total order.',
    ] as $detail) {
        expect($text)->toContain($detail);
    }

    expect($text)->not->toContain('Durasi:');
    if (! $denganVoucher) {
        expect($text)->not->toContain('Voucher:');
    }

    $component->searchTable($order->label_paket)->assertCanSeeTableRecords([$order]);
    $component->searchTable($order->kode_order)->assertCanSeeTableRecords([$order]);
    $component->filterTable('status', StatusLangganan::Disetujui->value)->assertCanNotSeeTableRecords([$order]);
    $component->filterTable('status', StatusLangganan::MenungguPembayaran->value)->assertCanSeeTableRecords([$order]);
    if ($role === 'user') {
        $component->assertTableActionVisible('konfirmasiPembayaran', $order)
            ->assertTableActionVisible('batalkan', $order)
            ->assertTableActionHidden('setujui', $order);
    } else {
        $order->update(['status' => StatusLangganan::MenungguVerifikasi]);
        $component->filterTable('status', StatusLangganan::MenungguVerifikasi->value)
            ->assertCanSeeTableRecords([$order])
            ->assertTableActionVisible('setujui', $order)
            ->assertTableActionVisible('tolak', $order)
            ->assertTableActionHidden('konfirmasiPembayaran', $order);
    }
})->with(['user', 'admin'])->with([true, false])->group('langganan');

test('order menyimpan snapshot paket dan metode pembayaran', function () {
    $user = User::factory()->create(['role' => 'user', 'type' => 'reguler', 'masa_aktif' => null]);
    $paket = PaketLangganan::factory()->create(['label' => 'Premium 30 Hari', 'harga' => 25000, 'durasi_hari' => 30]);
    $metode = MetodePembayaran::factory()->create([
        'label' => 'BCA',
        'nama_penyedia' => 'Bank BCA',
        'nomor_tujuan' => '123456',
    ]);

    $order = app(BuatOrderLangganan::class)->handle($user, $paket, $metode);

    expect($order->kode_order)->toStartWith('LGN-')
        ->and($order->status)->toBe(StatusLangganan::MenungguPembayaran)
        ->and($order->label_paket)->toBe('Premium 30 Hari')
        ->and($order->harga)->toBe(25000)
        ->and($order->durasi_hari)->toBe(30)
        ->and($order->label_metode_pembayaran)->toBe('BCA')
        ->and($order->detail_pembayaran['nomor_tujuan'])->toBe('123456');

    $paket->update(['label' => 'Nama Baru', 'harga' => 90000]);
    $metode->update(['nomor_tujuan' => '999999']);

    expect($order->fresh()->label_paket)->toBe('Premium 30 Hari')
        ->and($order->fresh()->harga)->toBe(25000)
        ->and($order->fresh()->detail_pembayaran['nomor_tujuan'])->toBe('123456');
})->group('langganan');

test('paket dan metode pembayaran nonaktif tidak dapat digunakan', function () {
    $user = User::factory()->create(['role' => 'user']);
    $paket = PaketLangganan::factory()->create(['is_active' => false]);
    $metode = MetodePembayaran::factory()->create();

    expect(fn () => app(BuatOrderLangganan::class)->handle($user, $paket, $metode))
        ->toThrow(ValidationException::class);

    $paket->update(['is_active' => true]);
    $metode->update(['is_active' => false]);

    expect(fn () => app(BuatOrderLangganan::class)->handle($user, $paket, $metode))
        ->toThrow(ValidationException::class);
})->group('langganan');

test('konfirmasi pembayaran hanya dapat dilakukan pemilik order', function () {
    Notification::fake();
    $pemilik = User::factory()->create(['role' => 'user']);
    $userLain = User::factory()->create(['role' => 'user']);
    $order = Langganan::factory()->create(['user_id' => $pemilik->id]);

    expect(fn () => app(KonfirmasiPembayaranLangganan::class)->handle($userLain, $order, 'bukti/file.jpg'))
        ->toThrow(AuthorizationException::class);

    $hasil = app(KonfirmasiPembayaranLangganan::class)->handle($pemilik, $order, 'bukti/file.jpg', 'Sudah ditransfer');

    expect($hasil->status)->toBe(StatusLangganan::MenungguVerifikasi)
        ->and($hasil->bukti_pembayaran_path)->toBe('bukti/file.jpg')
        ->and($hasil->tanggal_konfirmasi)->not->toBeNull();
})->group('langganan');

test('persetujuan mengaktifkan premium dan tidak menghilangkan sisa masa aktif', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'role' => 'user',
        'type' => 'premium',
        'masa_aktif' => today()->addDays(10),
    ]);
    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
        'durasi_hari' => 30,
        'bukti_pembayaran_path' => 'bukti/file.jpg',
    ]);

    $hasil = app(SetujuiLangganan::class)->handle($admin, $order);

    expect($hasil->status)->toBe(StatusLangganan::Disetujui)
        ->and($hasil->masa_aktif_mulai->toDateString())->toBe(today()->addDays(11)->toDateString())
        ->and($hasil->masa_aktif_sampai->toDateString())->toBe(today()->addDays(40)->toDateString())
        ->and($hasil->diverifikasi_oleh)->toBe($admin->id)
        ->and($user->fresh()->type)->toBe('premium')
        ->and($user->fresh()->masa_aktif->toDateString())->toBe(today()->addDays(40)->toDateString());

    $persetujuanUlang = app(SetujuiLangganan::class)->handle($admin, $hasil);

    expect($persetujuanUlang->status)->toBe(StatusLangganan::Disetujui)
        ->and($user->fresh()->masa_aktif->toDateString())->toBe(today()->addDays(40)->toDateString());
})->group('langganan');

test('penolakan wajib melalui admin dan tidak mengubah masa aktif', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user', 'type' => 'reguler', 'masa_aktif' => null]);
    $order = Langganan::factory()->create([
        'user_id' => $user->id,
        'status' => StatusLangganan::MenungguVerifikasi,
    ]);

    expect(fn () => app(TolakLangganan::class)->handle($user, $order, 'Tidak valid'))
        ->toThrow(AuthorizationException::class);

    $hasil = app(TolakLangganan::class)->handle($admin, $order, 'Nominal tidak sesuai');

    expect($hasil->status)->toBe(StatusLangganan::Ditolak)
        ->and($hasil->catatan_admin)->toBe('Nominal tidak sesuai')
        ->and($user->fresh()->masa_aktif)->toBeNull();
})->group('langganan');

test('riwayat user hanya menampilkan order miliknya sedangkan admin melihat semua', function () {
    $user = User::factory()->create(['role' => 'user']);
    $userLain = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);
    $orderSendiri = Langganan::factory()->create(['user_id' => $user->id]);
    $orderLain = Langganan::factory()->create(['user_id' => $userLain->id]);

    Livewire::actingAs($user)
        ->test(ListLangganans::class)
        ->assertCanSeeTableRecords([$orderSendiri])
        ->assertCanNotSeeTableRecords([$orderLain]);

    Livewire::actingAs($admin)
        ->test(ListLangganans::class)
        ->assertCanSeeTableRecords([$orderSendiri, $orderLain]);
})->group('langganan');

test('pengelolaan paket dan metode pembayaran hanya dapat diakses admin', function () {
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.paket-langganans.index'))
        ->assertForbidden();
    $this->actingAs($user)
        ->get(route('filament.admin.resources.metode-pembayarans.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.paket-langganans.index'))
        ->assertSuccessful();
    $this->actingAs($admin)
        ->get(route('filament.admin.resources.metode-pembayarans.index'))
        ->assertSuccessful();
})->group('langganan');

test('user dapat membuka halaman order sedangkan admin tidak dapat membuat order', function () {
    $user = createRegularUserWithBukuKas();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.langganans.create'))
        ->assertSuccessful();
    $this->actingAs($admin)
        ->get(route('filament.admin.resources.langganans.create'))
        ->assertForbidden();
})->group('langganan');

test('tabel perbandingan akun langganan sesuai batasan dan tersembunyi bagi admin', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $component = Livewire::actingAs($user)->test(ListLangganans::class)->assertSuccessful();
    $document = new DOMDocument;
    @$document->loadHTML(mb_convert_encoding($component->html(), 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($document);
    $tables = $xpath->query('//table[@data-testid="plan-comparison"]');

    expect($tables->length)->toBe($role === 'admin' ? 0 : 1);

    if ($role === 'user') {
        $table = $tables->item(0);
        expect($xpath->query('.//thead//th[@scope="col"]', $table))->toHaveCount(3);
        expect($xpath->query('.//tbody/tr', $table))->toHaveCount(9);
        expect($table->textContent)->not->toContain(
            'Aktivitas pemasukan dan pengeluaran',
            'Transfer antar-kas dan antar-dompet',
            'Pencatatan pemasukan dan pengeluaran',
            'Menerima akses kas bersama',
            'Mengelola atau mencabut kolaborasi milik sendiri yang sudah ada',
        );
        $expected = [
            'Jumlah kas' => ['Maksimal 2', 'Tidak terbatas'],
            'Jumlah Dompet' => ['Maksimal 2', 'Tidak terbatas'],
            'Membuat kolaborasi kas (Viewer / Editor)' => ['Tidak tersedia', 'Tersedia'],
            'Membuat link kas publik tanpa login (hanya lihat)' => ['Tidak tersedia', 'Tersedia'],
        ];
        foreach ($xpath->query('.//tbody/tr', $table) as $row) {
            $label = $xpath->query('./th[@scope="row"]', $row)->item(0)->textContent;
            $cells = $xpath->query('./td', $row);
            expect($cells)->toHaveCount(2);
            $values = [];
            foreach ($cells as $cell) {
                $status = $xpath->query('./span[@role="img"]', $cell)->item(0);
                if ($status !== null) {
                    expect(trim($cell->textContent))->toBe('');
                    expect($xpath->query('.//*[local-name()="svg" and @aria-hidden="true"]', $status))->toHaveCount(1);
                }
                $values[] = $status?->getAttribute('aria-label') ?? trim($cell->textContent);
            }
            expect($values)
                ->toBe($expected[$label] ?? ['Tersedia', 'Tersedia']);
        }
        expect($xpath->query('.//thead', $table)->item(0)->textContent)
            ->toContain('Free (Reguler)', 'Premium', 'Selama masa aktif berlaku');
        expect($xpath->query('//ul[@id="plan-access-notes"]')->item(0)->textContent)
            ->toContain('tidak mengurangi kuota kas sendiri', 'Saat Premium berakhir', 'Editor hanya dapat mencatat pada kas yang masih dapat dikelola pemilik', 'hanya tersedia bagi pemilik kas');
    }
})->with(['user', 'admin'])->group('langganan');
