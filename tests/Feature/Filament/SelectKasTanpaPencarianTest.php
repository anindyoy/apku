<?php

use App\Filament\Forms\SelectKasDanSumberDana;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\TabunganEmas;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Livewire;

test('select kas kolaborator tanpa pencarian saat opsi 7 atau kurang', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();

    Livewire::actingAs($user)->test(ListShareBukus::class)
        ->mountAction('create')
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => ! $field->isSearchable()
            && ! $field->isPreloaded()
            && ($field->getOptions()[$kas->id] ?? null) === $kas->nama_buku);
});

test('select kas kolaborator menggunakan searchable dan preload jika opsinya lebih dari 7', function () {
    $user = createPremiumUserWithBukuKas();

    // Buat hingga total 8 kas (lebih dari 7 opsi).
    while ($user->buku_kas()->count() < 8) {
        BukuKas::factory()->create(['user_id' => $user->id]);
    }

    Livewire::actingAs($user)->test(ListShareBukus::class)
        ->mountAction('create')
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => $field->isSearchable()
            && $field->isPreloaded()
            && count($field->getOptions()) > 7);
});

test('select kas tujuan tanpa pencarian tetap mengecualikan kas asal saat opsi 7 atau kurang', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();
    $tujuan = $user->buku_kas()->create(['nama_buku' => 'Kas tujuan', 'saldo' => 0]);
    TabunganEmas::factory()->create(['buku_kas_id' => $kas->id]);

    Livewire::actingAs($user)->test(ListBukuKas::class)
        ->mountTableAction('hapusDanPindahkan', $kas)
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => ! $field->isSearchable()
            && ! $field->isPreloaded()
            && ! array_key_exists($kas->id, $field->getOptions())
            && ($field->getOptions()[$tujuan->id] ?? null) === $tujuan->nama_buku);
});

test('select kas tujuan menggunakan searchable dan preload jika opsi kas tujuan lebih dari 7', function () {
    $user = createPremiumUserWithBukuKas();
    $kas = $user->buku_kas()->first();
    TabunganEmas::factory()->create(['buku_kas_id' => $kas->id]);

    // Tambah kas tujuan hingga opsi kas tujuan (tidak termasuk kas asal) berjumlah 8.
    while ($user->buku_kas()->whereKeyNot($kas->id)->count() < 8) {
        BukuKas::factory()->create(['user_id' => $user->id]);
    }

    Livewire::actingAs($user)->test(ListBukuKas::class)
        ->mountTableAction('hapusDanPindahkan', $kas)
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => $field->isSearchable()
            && $field->isPreloaded()
            && count($field->getOptions()) > 7);
});

test('select sumber dana dan kas pada form tambah transaksi memakai searchable dan preload saat opsi lebih dari 7', function () {
    $user = createPremiumUserWithBukuKas();

    // Pastikan kas dan sumber dana memiliki lebih dari 7 opsi.
    while ($user->buku_kas()->count() < 8) {
        BukuKas::factory()->create(['user_id' => $user->id]);
    }

    while ($user->sumberDana()->count() < 8) {
        SumberDana::factory()->create(['user_id' => $user->id]);
    }

    Livewire::actingAs($user)->test(ListTransaksis::class)
        ->mountAction('tambahTransaksi')
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => $field->isSearchable()
            && $field->isPreloaded()
            && count($field->getOptions()) > 7)
        ->assertFormFieldExists('dompet_id', fn (Select $field): bool => $field->isSearchable()
            && $field->isPreloaded()
            && count($field->getOptions()) > 7);
});

test('helper SelectKasDanSumberDana menerapkan ambang batas 7 opsi secara tepat', function () {
    // 7 opsi: tidak searchable dan tidak preloaded.
    $selectTujuh = Select::make('kas')->options(array_combine(range(1, 7), range(1, 7)));
    SelectKasDanSumberDana::terapkan($selectTujuh);
    expect($selectTujuh->isSearchable())->toBeFalse()
        ->and($selectTujuh->isPreloaded())->toBeFalse();

    // 8 opsi: harus searchable dan preloaded.
    $selectDelapan = Select::make('kas')->options(array_combine(range(1, 8), range(1, 8)));
    SelectKasDanSumberDana::terapkan($selectDelapan);
    expect($selectDelapan->isSearchable())->toBeTrue()
        ->and($selectDelapan->isPreloaded())->toBeTrue();

    // Pengujian pada SelectFilter dengan 7 vs 8 opsi.
    $filterTujuh = SelectFilter::make('kas')->options(array_combine(range(1, 7), range(1, 7)));
    SelectKasDanSumberDana::terapkanFilter($filterTujuh);
    expect($filterTujuh->getSearchable())->toBeFalse()
        ->and($filterTujuh->isPreloaded())->toBeFalse();

    $filterDelapan = SelectFilter::make('kas')->options(array_combine(range(1, 8), range(1, 8)));
    SelectKasDanSumberDana::terapkanFilter($filterDelapan);
    expect($filterDelapan->getSearchable())->toBeTrue()
        ->and($filterDelapan->isPreloaded())->toBeTrue();
});

