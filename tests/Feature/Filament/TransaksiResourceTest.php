<?php

use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\Kategori;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ==================== TRANSAKSI RESOURCE ====================

test('transaksi resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'tanggal' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->assertSeeText('Rp');
})
    ->group('filament', 'transaksi');

test('kolom kategori transaksi selalu diawali huruf kapital', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    $jenis = Kategori::factory()->untukKas($bukuKas)->create([
        'user_id' => $user->id,
        'nama' => 'transfer',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'kategori_id' => $jenis->id,
    ]);

    expect(TransaksiResource::getKategoriLabel($transaksi))->toBe('Transfer');
})
    ->group('filament', 'transaksi', 'kategori-kapital');

test('transfer sumber dana tidak menampilkan atribut kas pada daftar transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();
    $kasTransfer = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Transfer Pemasukan',
        'tipe_transfer' => 'dompet',
        'tanggal' => now(),
    ]);
    $kasBiasa = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'tanggal' => now(),
    ]);

    $kolomKas = TransaksiResource::transactionColumns()[3];
    $callback = (new ReflectionClass($kolomKas))->getProperty('formatStateUsing')->getValue($kolomKas);

    expect($callback($bukuKas->nama_buku, $kasTransfer))->toBeNull()
        ->and($callback($bukuKas->nama_buku, $kasBiasa))->toBe($bukuKas->nama_buku);
})
    ->group('filament', 'transaksi', 'transfer-sumber-dana-kas');

test('ringkasan mobile menampilkan deskripsi dan saldo akhir saat filter kas aktif', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 50000,
        'deskripsi' => 'Setoran awal',
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->withQueryParams(['filter_buku_kas' => (string) $bukuKas->id])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->html();

    expect($html)
        ->toContain('Deskripsi: Setoran awal')
        ->toContain('Saldo Kas');
})
    ->group('filament', 'transaksi', 'mobile-saldo-deskripsi');

test('ringkasan mobile menampilkan saldo akhir saat filter kas berasal dari URL', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 75000,
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->withQueryParams(['filter_buku_kas' => (string) $bukuKas->id])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->html();

    expect($html)
        ->toContain('Saldo Kas')
        ->toContain('Rp 75.000');
})
    ->group('filament', 'transaksi', 'mobile-saldo-deskripsi');

test('ringkasan mobile tidak menampilkan saldo kas untuk transfer sumber dana', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Transfer Pemasukan',
        'tipe_transfer' => 'dompet',
        'nominal' => 25000,
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->withQueryParams(['filter_buku_kas' => (string) $bukuKas->id])
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->html();

    expect($html)->not->toContain('Saldo Kas');
})
    ->group('filament', 'transaksi', 'mobile-saldo-deskripsi');

test('warna record transaksi dibedakan berdasarkan tipe transaksi', function () {
    expect(TransaksiResource::getWarnaTipeTransaksi('Pemasukan'))->toBe('success')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Pengeluaran'))->toBe('danger')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pemasukan'))->toBe('info')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pengeluaran'))->toBe('info')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pemasukan', 'dompet'))->toBe('warning');
})
    ->group('filament', 'transaksi', 'warna-tipe-transaksi');

test('baris daftar transaksi memakai latar sesuai tipe dan teks hitam', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->firstOrFail();

    $transaksis = collect([
        ['jenis' => 'Pemasukan', 'tipe_transfer' => null, 'tone' => 'success'],
        ['jenis' => 'Pengeluaran', 'tipe_transfer' => null, 'tone' => 'danger'],
        ['jenis' => 'Transfer Pemasukan', 'tipe_transfer' => 'buku_kas', 'tone' => 'info'],
        ['jenis' => 'Transfer Pemasukan', 'tipe_transfer' => 'dompet', 'tone' => 'warning'],
    ])->map(fn (array $data) => Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => $data['jenis'],
        'tipe_transfer' => $data['tipe_transfer'],
        'tanggal' => now(),
    ]));

    $component = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertCanSeeTableRecords($transaksis);
    $html = $component->html();

    expect($html)->toContain('fi-ac-link-action fi-link');

    $component
        ->assertTableActionHasColor('edit', 'info', $transaksis->first())
        ->assertTableActionHasColor('delete', 'danger', $transaksis->first());

    foreach (['success', 'danger', 'info', 'warning'] as $tone) {
        expect($html)->toContain('transaction-record--'.$tone);
    }

    $styles = file_get_contents(resource_path('css/filament-toolbar.css'));
    expect($styles)
        ->toContain('html:not(.dark) .fi-ta-row.transaction-record > .fi-ta-cell:not(:has(.fi-ta-actions)) *')
        ->not->toContain('.fi-ta-row.transaction-record > .fi-ta-cell *')
        ->toContain('color: #000 !important;')
        ->toContain('border-bottom: 1px solid rgb(100 116 139 / 0.35) !important;')
        ->toContain('.fi-ta-row.transaction-record > .fi-ta-cell:has(.fi-ta-actions)')
        ->toContain('border-bottom: 0 !important;')
        ->toContain('.dark .fi-ta-row.transaction-record:nth-child(odd) > .fi-ta-cell')
        ->toContain('.dark .fi-ta-row.transaction-record:nth-child(even) > .fi-ta-cell')
        ->toContain('.dark .fi-ta-row.transaction-record--success > .fi-ta-cell:not(:has(.fi-ta-actions)) *')
        ->toContain('.dark .fi-ta-row.transaction-record--danger > .fi-ta-cell:not(:has(.fi-ta-actions)) *')
        ->toContain('.dark .fi-ta-row.transaction-record--info > .fi-ta-cell:not(:has(.fi-ta-actions)) *')
        ->toContain('.fi-ta-row.transaction-record .fi-ta-actions .fi-link.fi-color-warning')
        ->toContain('.fi-ta-row.transaction-record .fi-ta-actions .fi-link.fi-color-info')
        ->toContain('.fi-ta-row.transaction-record .fi-ta-actions .fi-link.fi-color-danger')
        ->toContain('@media (max-width: 639px)')
        ->toContain('padding: 0.3rem 0.55rem;')
        ->toContain('font-size: 0.75rem;')
        ->toContain('html:not(.dark) .fi-ta-row.transaction-record--success > .fi-ta-cell')
        ->toContain('html:not(.dark) .fi-ta-row.transaction-record--danger > .fi-ta-cell')
        ->toContain('html:not(.dark) .fi-ta-row.transaction-record--info > .fi-ta-cell')
        ->toContain('html:not(.dark) .fi-ta-row.transaction-record--warning > .fi-ta-cell');
})
    ->group('filament', 'transaksi', 'warna-baris-transaksi');

test('tabel transaksi merangkum pencatat dan dompet pada deskripsi kolom', function () {
    $user = createRegularUserWithBukuKas();
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'jenis' => 'Pemasukan',
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertCanSeeTableRecords([$transaksi])
        ->html();

    expect($html)
        ->not->toContain('Dicatat oleh:')
        ->toContain('Sumber dana: '.$transaksi->labelDompetUntuk($user));
})
    ->group('filament', 'transaksi', 'ringkasan-kolom');

test('filter transaksi berada dalam section Filament yang tertutup secara default', function () {
    $user = createRegularUserWithBukuKas();

    $html = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertSuccessful()
        ->html();

    expect($html)
        ->toContain('data-testid="filter-transaksi-section"')
        ->toContain('aria-expanded="false"')
        ->toContain('fi-collapsible')
        ->toContain('Filter transaksi')
        ->toContain('Reset filter')
        ->toContain('bg-gray-100')
        ->toContain('border-gray-400')
        ->toContain('dark:bg-white/5');
})
    ->group('filament', 'transaksi', 'filter-section');

test('daftar transaksi mobile menampilkan ringkasan tanpa kolom desktop', function () {
    $user = createRegularUserWithBukuKas();
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'jenis' => 'Pemasukan',
        'nominal' => 123456,
        'tanggal' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->test(ListTransaksis::class)
        ->assertCanSeeTableRecords([$transaksi])
        ->html();

    $bukuKas = $user->buku_kas()->firstOrFail();
    $sumberDana = $user->sumberDana()->firstOrFail();

    expect($html)
        ->toContain('data-transaksi-mobile')
        ->toContain('data-tone="success"')
        ->toContain('transaction-mobile-summary')
        ->toContain('Rp 123.456')
        ->toContain('Kas:')
        ->toContain($bukuKas->nama_buku)
        ->toContain('Sumber Dana:')
        ->toContain($sumberDana->nama_dompet)
        ->toContain('fi-ta-table-stacked-on-mobile');

    expect(Livewire::actingAs($user)->test(ListTransaksis::class)->instance()->getTable()->getRecordAction($transaksi))
        ->toBe('edit');

    $columns = collect(TransaksiResource::transactionColumns())->keyBy(fn ($column) => $column->getName());
    expect($columns->get('ringkasan_mobile')->getHiddenFrom())->toBe('md')
        ->and($columns->get('tanggal')->getVisibleFrom())->toBe('md')
        ->and($columns->get('nominal')->getVisibleFrom())->toBe('md');
})
    ->group('filament', 'transaksi', 'mobile-ringkasan');

test('header transaksi mengikuti visibilitas responsif isi kolom', function () {
    $user = createRegularUserWithBukuKas();
    $html = Livewire::actingAs($user)->test(ListTransaksis::class)->html();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);

    $headers = $xpath->query('//th[contains(@class, "fi-ta-header-cell") and @scope="col"]');
    $labels = [];
    foreach ($headers as $header) {
        $label = trim($header->textContent);
        if ($label === '') {
            continue;
        }
        $labels[] = $label;
        expect($header->getAttribute('class'))->toContain($label === 'Transaksi' ? 'md:fi-hidden' : 'md:fi-visible');
    }
    expect($labels)->toBe(['Transaksi', 'Tipe', 'Tanggal', 'Kas', 'Kategori', 'Nominal']);

    $script = <<<'JS'
    import assert from 'node:assert/strict';
    import fs from 'node:fs';
    import postcss from 'postcss';
    import tailwind from 'tailwindcss';
    const result = await postcss([tailwind({ content: [] })]).process(fs.readFileSync('resources/css/filament-toolbar.css', 'utf8'), { from: undefined });
    const declarations = [];
    result.root.walkDecls('display', decl => {
        if (decl.parent.selector?.includes('> thead > tr > .fi-ta-header-cell.md\\:fi-')) {
            declarations.push([decl.parent.selector.split('fi-header-cell').pop(), decl.value, decl.important, decl.parent.parent.params ?? null]);
        }
    });
    const hidden = declarations.filter(([selector]) => selector.includes('fi-hidden'));
    const visible = declarations.filter(([selector]) => selector.includes('fi-visible'));
    assert.deepEqual(hidden.map(([, ...values]) => values), [['none', true, '(min-width: 768px)']]);
    assert.deepEqual(visible.map(([, ...values]) => values), [['none', true, null], ['table-cell', true, '(min-width: 768px)']]);
    const cellSpacing = [];
    result.root.walkRules(rule => {
        if (rule.selector.includes('.fi-ta-cell') && !rule.selector.includes(':')) {
            rule.walkDecls(/padding-(top|bottom)/, decl => cellSpacing.push([decl.prop, decl.value, decl.important]));
        }
    });
    assert.deepEqual(cellSpacing, [['padding-top', '0.5rem', true], ['padding-bottom', '0.5rem', true]]);
    JS;
    $process = new \Symfony\Component\Process\Process(['node', '--input-type=module'], base_path());
    $process->setInput($script)->run();
    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
});

test('transaksi resource dapat mengedit nominal transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = Kategori::factory()->untukKas($bukuKas)->create([
        'user_id' => $user->id,
        'nama' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'kategori_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->assertSuccessful()
        ->set('data.nominal', 150000)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals(150000, $transaksi->fresh()->nominal);
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat menghapus transaksi pemasukan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();

    $jenis = Kategori::factory()->untukKas($bukuKas)->create([
        'user_id' => $user->id,
        'nama' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'kategori_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->callTableAction('delete', $transaksi->id);

    $this->assertEmpty(DB::table('transaksi')->find($transaksi->id));
})
    ->group('filament', 'transaksi');
