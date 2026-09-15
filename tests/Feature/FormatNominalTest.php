<?php

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
