<?php

test('bisa membuka halaman akun saya', function () {
    $this->renderTest('akun_saya');
})
    ->group('render');

test('bisa membuka halaman buku kas', function () {
    $this->renderTest('buku_kas');
})
    ->group('render');

test('bisa membuka halaman kategori', function () {
    $this->renderTest('kategori');
})
    ->group('render');

test('bisa membuka halaman transaksi', function () {
    $this->renderTest('transaksi');
})
    ->group('render');

test('bisa membuka halaman piutang', function () {
    $this->renderTest('piutang');
})
    ->group('render');

test('bisa membuka halaman utang', function () {
    $this->renderTest('utang');
})
    ->group('render');

test('bisa membuka halaman register', function () {
    $this->renderTest('register');
})
    ->group('render');


test('bisa membuka halaman piutang detail', function () {
    $this->renderTest('detail_piutang', true);
})
    ->group('render');

test('bisa membuka halaman edit user', function () {
    $this->renderTest('edit_user', true);
})
    ->group('render');
