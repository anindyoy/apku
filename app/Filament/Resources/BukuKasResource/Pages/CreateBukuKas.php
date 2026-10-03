<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use App\Services\KategoriService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBukuKas extends CreateRecord
{
    protected static string $resource = BukuKasResource::class;

    public bool $hubungkanKategoriKasBaru = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        // Pilihan ini bukan kolom kas, jadi disimpan sementara sampai kas selesai dibuat.
        $this->hubungkanKategoriKasBaru = (bool) ($data['hubungkan_kategori'] ?? true);
        unset($data['hubungkan_kategori']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->hubungkanKategoriKasBaru) {
            app(KategoriService::class)->hubungkanSemuaKategoriPemilik($this->record);
        }
    }
}
