<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Resources\BukuKasResource;
use App\Models\Kategori;
use App\Services\KategoriService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBukuKas extends CreateRecord
{
    protected static string $resource = BukuKasResource::class;

    public bool $hubungkanKategoriKasBaru = false;

    /** @var array<int, int> */
    public array $kategoriKasBaru = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        // Pilihan ini bukan kolom kas, jadi disimpan sementara sampai kas selesai dibuat.
        $kategori = (array) ($data['kategori'] ?? []);
        if (empty($kategori) && (bool) ($data['hubungkan_kategori'] ?? false)) {
            $this->kategoriKasBaru = Kategori::withoutGlobalScopes()
                ->where('user_id', Auth::id())
                ->pluck('id')
                ->all();
        } else {
            $this->kategoriKasBaru = array_values(array_map('intval', $kategori));
        }
        $this->hubungkanKategoriKasBaru = ! empty($this->kategoriKasBaru);
        unset($data['kategori'], $data['hubungkan_kategori'], $data['aktifkan_di_semua_kategori']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->kategoriKasBaru)) {
            app(KategoriService::class)->hubungkanKasKeKategori($this->record, $this->kategoriKasBaru);
        }
    }
}
