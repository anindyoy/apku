<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus;
use App\Models\TabunganEmas;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

test('select kas kolaborator tanpa pencarian tetap menyediakan kas pemilik', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();

    Livewire::actingAs($user)->test(ListShareBukus::class)
        ->mountAction('create')
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => ! $field->isSearchable()
            && ($field->getOptions()[$kas->id] ?? null) === $kas->nama_buku);
});

test('select kas tujuan tanpa pencarian tetap mengecualikan kas asal', function () {
    $user = createRegularUserWithBukuKas();
    $kas = $user->buku_kas()->first();
    $tujuan = $user->buku_kas()->create(['nama_buku' => 'Kas tujuan', 'saldo' => 0]);
    TabunganEmas::factory()->create(['buku_kas_id' => $kas->id]);

    Livewire::actingAs($user)->test(ListBukuKas::class)
        ->mountTableAction('hapusDanPindahkan', $kas)
        ->assertFormFieldExists('buku_kas_id', fn (Select $field): bool => ! $field->isSearchable()
            && ! array_key_exists($kas->id, $field->getOptions())
            && ($field->getOptions()[$tujuan->id] ?? null) === $tujuan->nama_buku);
});
