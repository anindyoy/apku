<?php

describe('pemulihan kata sandi', function () {
    it('menampilkan tautan lupa kata sandi pada halaman masuk', function () {
        $response = $this->get('/app/login')->assertOk();

        expect($response->getContent())->toContain(route('filament.app.auth.password-reset.request'));
    });

    it('menyediakan halaman permintaan kata sandi', function () {
        $this->get('/app/password-reset/request')->assertOk();
    });
});
