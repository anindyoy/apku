<?php

namespace App\Filament\Resources\ShareBukuResource\Pages;

use App\Filament\Resources\ShareBukuResource;
use App\Models\ShareBuku;
use App\Notifications\BukuDibagikan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateShareBuku extends CreateRecord
{
    protected static string $resource = ShareBukuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (ShareBuku::query()->where('buku_kas_id', $data['buku_kas_id'])->where('user_id', $data['user_id'])->exists()) {
            throw ValidationException::withMessages(['data.user_id' => 'Pengguna sudah menjadi kolaborator buku ini.']);
        }

        $data['invited_by_user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->user->notify(new BukuDibagikan($this->record));
    }
}
