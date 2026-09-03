<?php

use App\Filament\Resources\TransaksiResource;
use App\Models\Transaksi;

test('label kategori transfer tetap tersedia ketika relasi buku tidak tersedia', function () {
    $transferMasuk = new Transaksi(['jenis' => 'Transfer Pemasukan']);
    $transferMasuk->setRelation('asal_buku_tabungan', null);

    $transferKeluar = new Transaksi(['jenis' => 'Transfer Pengeluaran']);
    $transferKeluar->setRelation('tujuan_buku_tabungan', null);

    expect(TransaksiResource::getKategoriLabel($transferMasuk))->toBe('Transfer dari -')
        ->and(TransaksiResource::getKategoriLabel($transferKeluar))->toBe('Transfer ke -');
});
