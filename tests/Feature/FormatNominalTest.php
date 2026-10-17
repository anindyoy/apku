<?php

use App\Filament\Forms\BukuKasFormSchema;
use App\Filament\Forms\KomponenNominal;
use App\Filament\Forms\SumberDanaFormSchema;
use App\Filament\Resources\AuditSaldoDompetResource;
use App\Filament\Resources\LanggananResource;
use App\Filament\Resources\PaketLanggananResource;
use App\Filament\Resources\TabunganEmasResource;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

test('nominal rupiah seluruh kolom mata uang tampil tanpa desimal', function () {
    foreach ([
        LanggananResource::class => ['total_pembayaran'],
        PaketLanggananResource::class => ['harga'],
        AuditSaldoDompetResource::class => ['total_saldo_aplikasi', 'total_saldo_riil', 'total_selisih'],
        TabunganEmasResource::class => ['harga_beli'],
    ] as $resource => $names) {
        $table = $resource::table(Table::make(Mockery::mock(HasTable::class)));
        foreach ($names as $name) {
            $column = $table->getColumn($name);
            foreach ([0 => 'Rp0', 25000 => 'Rp25.000', -12500 => '-Rp12.500', 1250000 => 'Rp1.250.000'] as $value => $expected) {
                $formatted = preg_replace('/\s+/u', '', $column->formatState($value));
                expect($formatted)->toBe($expected, $resource.'::'.$name);
            }
        }
    }

    $table = TabunganEmasResource::table(Table::make(Mockery::mock(HasTable::class)));
    expect($table->getColumn('berat_gram')->formatState('1.2345'))->toBe('1,2345 gram');
});

test('komponen nominal memformat input rupiah dengan separator ribuan dan tanpa desimal', function () {
    expect(KomponenNominal::formatKeUi(50000))->toBe('50.000');
    expect(KomponenNominal::formatKeUi(1250000))->toBe('1.250.000');
    expect(KomponenNominal::formatKeUi(0))->toBe('0');
    expect(KomponenNominal::formatKeUi('0'))->toBe('0');
    expect(KomponenNominal::formatKeUi('1.250.000'))->toBe('1.250.000');
    expect(KomponenNominal::formatKeUi(null))->toBeNull();
    expect(KomponenNominal::formatKeUi(''))->toBeNull();

    expect(KomponenNominal::bersihkanKeInteger('50.000'))->toBe(50000);
    expect(KomponenNominal::bersihkanKeInteger('1.250.000'))->toBe(1250000);
    expect(KomponenNominal::bersihkanKeInteger('0'))->toBe(0);
    expect(KomponenNominal::bersihkanKeInteger(0))->toBe(0);
    expect(KomponenNominal::bersihkanKeInteger(75000))->toBe(75000);
    expect(KomponenNominal::bersihkanKeInteger(null))->toBeNull();
    expect(KomponenNominal::bersihkanKeInteger(''))->toBeNull();
});

test('skema input nominal pada formulir buku kas dan sumber dana terkonfigurasi mask dan separator', function () {
    $fieldsKas = BukuKasFormSchema::fields();
    $saldoKas = collect($fieldsKas)->first(fn ($field) => $field->getName() === 'saldo');
    expect($saldoKas)->not->toBeNull();
    expect($saldoKas->getPrefixLabel())->toBe('Rp');
    expect((string) $saldoKas->getMask())->toContain('$money($input');

    $fieldsSumberDana = SumberDanaFormSchema::fields();
    $saldoSumberDana = collect($fieldsSumberDana)->first(fn ($field) => $field->getName() === 'saldo');
    expect($saldoSumberDana)->not->toBeNull();
    expect($saldoSumberDana->getPrefixLabel())->toBe('Rp');
    expect((string) $saldoSumberDana->getMask())->toContain('$money($input');
});
