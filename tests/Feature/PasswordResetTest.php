<?php

describe('pemulihan kata sandi', function () {
    it('menampilkan tautan lupa kata sandi pada halaman masuk', function () {
        $response = $this->get('/admin/login')->assertOk();

        expect($response->getContent())->toContain(route('filament.admin.auth.password-reset.request'));
    });

    it('menyediakan halaman permintaan kata sandi', function () {
        $this->get('/admin/password-reset/request')->assertOk();
    });
});
