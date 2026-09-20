<?php

use App\Models\Transaksi;

test('transaksi baru mempengaruhi saldo secara default sebelum dibaca ulang', function () {
    expect((new Transaksi)->pengaruhi_saldo)->toBeTrue();
});

test('transaksi riwayat mempertahankan pilihan tanpa dampak saldo', function () {
    expect((new Transaksi(['pengaruhi_saldo' => false]))->pengaruhi_saldo)->toBeFalse();
});
