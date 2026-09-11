<?php

namespace App\Filament\Resources\ShareBukuResource\Pages;

use App\Filament\Resources\ShareBukuResource;
use App\Models\ShareBuku;
use App\Notifications\BukuDibagikan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListShareBukus extends ListRecords
{
    protected static string $resource = ShareBukuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    if (ShareBuku::query()->where('buku_kas_id', $data['buku_kas_id'])->where('user_id', $data['user_id'])->exists()) {
                        throw ValidationException::withMessages([
                            $this->getMountedActionSchema()->getStatePath().'.user_id' => 'Pengguna sudah menjadi kolaborator kas ini.',
                        ]);
                    }

                    $data['invited_by_user_id'] = auth()->id();

                    return $data;
                })
                ->after(function (ShareBuku $record): void {
                    $record->user->notify(new BukuDibagikan($record));
                }),
        ];
    }
}
