<?php

use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Models\User;
use App\Models\UtangPiutang;
use Filament\Actions\Action;
use Livewire\Livewire;

test('aksi daftar utang piutang menampilkan ikon beserta label teks', function (string $page, string $tipe) {
    $user = User::notAdmin()->firstOrFail();
    $record = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'tipe' => $tipe,
    ]);

    Livewire::actingAs($user)
        ->test($page)
        ->assertSuccessful()
        ->assertTableActionExists('Detail', function (Action $action) use ($record, $tipe): bool {
            expect($action->isLabelHidden())->toBeFalse()
                ->and($action->getLabel())->toBe('Detail')
                ->and($action->getIcon())->toBe('heroicon-o-magnifying-glass')
                ->and($action->getUrl())->toBe(url('/admin/'.$tipe.'s/'.$record->code.'/detail'));

            return true;
        }, $record)
        ->assertTableActionExists('delete', function (Action $action): bool {
            expect($action->isLabelHidden())->toBeFalse()
                ->and($action->getLabel())->toBe('Hapus')
                ->and($action->getIcon())->not->toBeEmpty();

            return true;
        }, $record);
})->with([
    'utang' => [ListUtangs::class, 'utang'],
    'piutang' => [ListPiutangs::class, 'piutang'],
]);
