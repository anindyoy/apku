<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Hooks: Tests\Feature\Filament
|--------------------------------------------------------------------------
|
| Hook ini hanya berlaku untuk test di dalam tests/Feature/Filament.
| Helper bersama (createRegularUserWithBukuKas, createSuperUser) berada
| di tests/Helpers/Filament.php yang di-load otomatis oleh Pest.
|
*/

beforeEach(function () {
    $this->seed([
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\JenisTransaksiSeeder::class,
        \Database\Seeders\WilayahSeeder::class,
    ]);
})->in('Feature/Filament');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
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
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
