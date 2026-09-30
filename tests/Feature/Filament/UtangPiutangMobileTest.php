<?php

use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use Livewire\Livewire;

test('daftar utang piutang memakai kartu mobile dengan data dan pencarian', function (string $page, string $tipe) {
    $user = User::notAdmin()->firstOrFail();
    $record = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => $tipe,
        'kepada' => 'Pihak kartu mobile',
        'deskripsi' => 'Catatan kartu mobile',
        'tempo' => now()->addWeek(),
    ]);
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $record->id,
        'tipe' => 'tambah',
        'nominal' => 500000,
    ]);
    UtangPiutangDetail::factory()->create([
        'utang_piutang_id' => $record->id,
        'tipe' => 'kurang',
        'nominal' => 200000,
    ]);

    $component = Livewire::actingAs($user)->test($page)
        ->assertSuccessful()
        ->searchTable('Pihak kartu mobile')
        ->assertCanSeeTableRecords([$record])
        ->assertTableColumnStateSet('nominal', 300000, $record)
        ->assertTableColumnStateSet('status', 'belum', $record)
        ->assertTableActionExists('Detail', record: $record)
        ->assertTableActionExists('delete', record: $record);

    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $xpath = new DOMXPath($document);
    $tables = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " fi-ta-table-stacked-on-mobile ")]');
    expect($tables)->toHaveCount(1);
    expect($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " utang-piutang-compact ")]//table'))->toHaveCount(1);
    expect($tables->item(0)->textContent)->toContain('Pihak kartu mobile', 'Catatan kartu mobile', 'Jatuh tempo:', 'Aktivitas terakhir:');

    $component->searchTable('Nama tidak ditemukan')->assertCanNotSeeTableRecords([$record]);
})->with([
    'utang' => [ListUtangs::class, 'utang'],
    'piutang' => [ListPiutangs::class, 'piutang'],
]);

test('tutorial utang piutang menjelaskan kartu mobile dan tabel desktop', function () {
    $response = $this->get(route('tutorial'))->assertOk();
    $topic = collect($response->viewData('topics'))->firstWhere('id', 'utang-piutang');

    expect($topic['intro'])->toContain('Pada mobile', 'kartu', 'Detail', 'Hapus', 'Pada desktop');
});
