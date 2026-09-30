<?php

use App\Filament\Resources\TransaksiResource;
use App\Filament\Resources\TransaksiResource\Pages\EditTransaksi;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\JenisTransaksi;
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

test('kolom aktivitas transaksi selalu diawali huruf kapital', function () {
    $user = createRegularUserWithBukuKas();
    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'transfer',
    ]);
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $user->buku_kas()->firstOrFail()->id,
        'jenis' => 'Pemasukan',
        'jenis_transaksi_id' => $jenis->id,
    ]);

    expect(TransaksiResource::getKategoriLabel($transaksi))->toBe('Transfer');
})
    ->group('filament', 'transaksi', 'aktivitas-kapital');

test('warna record transaksi dibedakan berdasarkan tipe transaksi', function () {
    expect(TransaksiResource::getWarnaTipeTransaksi('Pemasukan'))->toBe('success')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Pengeluaran'))->toBe('danger')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pemasukan'))->toBe('info')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pengeluaran'))->toBe('info')
        ->and(TransaksiResource::getWarnaTipeTransaksi('Transfer Pemasukan', 'dompet'))->toBe('warning');
})
    ->group('filament', 'transaksi', 'warna-tipe-transaksi');

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
        ->toContain('Dompet: '.$transaksi->labelDompetUntuk($user));
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
        ->toContain('Reset filter');
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

    expect($html)
        ->toContain('data-transaksi-mobile')
        ->toContain('data-tone="success"')
        ->toContain('transaction-mobile-amount')
        ->toContain('Rp 123.456')
        ->toContain('fi-ta-table-stacked-on-mobile');

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
    expect($labels)->toBe(['Transaksi', 'Tipe', 'Tanggal', 'Kas', 'Aktivitas', 'Nominal']);

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

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'jenis_transaksi_id' => $jenis->id,
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

    $jenis = JenisTransaksi::factory()->create([
        'user_id' => $user->id,
        'nama_jenis' => 'Pemasukan',
    ]);

    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
        'jenis_transaksi_id' => $jenis->id,
    ]);

    Livewire::actingAs($user)
        ->test(EditTransaksi::class, ['record' => $transaksi->id])
        ->callTableAction('delete', $transaksi->id);

    $this->assertEmpty(DB::table('transaksi')->find($transaksi->id));
})
    ->group('filament', 'transaksi');
