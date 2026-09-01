<?php

$testToken = $_SERVER['TEST_TOKEN'] ?? $_ENV['TEST_TOKEN'] ?? getenv('TEST_TOKEN');

if ($testToken !== false && $testToken !== null && $testToken !== '') {
    $_SERVER['TEST_TOKEN'] = (string) $testToken;
    $_ENV['TEST_TOKEN'] = (string) $testToken;
    $_SERVER['LARAVEL_PARALLEL_TESTING'] = '1';
    $_ENV['LARAVEL_PARALLEL_TESTING'] = '1';
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Closure pada fungsi test selalu terikat pada class test PHPUnit tertentu. Konfigurasi
| berikut mengikat seluruh feature test ke class test aplikasi.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Saat menulis test, nilai perlu diperiksa terhadap kondisi tertentu. API Expectation dapat
| diperluas kapan saja untuk menambahkan assertion khusus aplikasi ini.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Helper yang digunakan bersama oleh beberapa file test dapat didefinisikan di bagian ini.
|
*/

function something()
{
    // Tambahkan helper test umum di sini.
}
