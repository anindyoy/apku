<?php

namespace App\Filament\Resources\BukuKasResource\Pages;

use App\Filament\Forms\BukuKasFormSchema;
use App\Filament\Resources\BukuKasResource;
use App\Services\KategoriService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditBukuKas extends EditRecord
{
    protected static string $resource = BukuKasResource::class;

    /** @var array<int, int>|null */
    public ?array $kategoriDipilih = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $opsi = BukuKasFormSchema::opsiKategori($this->getRecord());
        $terpilih = $this->getRecord()->idKategori();
        $data['kategori'] = $terpilih;
        $data['aktifkan_di_semua_kategori'] = ! empty($opsi) && count(array_intersect(array_keys($opsi), $terpilih)) === count($opsi);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->kategoriDipilih = array_key_exists('kategori', $data) ? (array) $data['kategori'] : null;
        unset($data['kategori'], $data['aktifkan_di_semua_kategori'], $data['hubungkan_kategori']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->kategoriDipilih !== null) {
            app(KategoriService::class)->sinkronkanKasDenganKategori(Auth::user(), $this->getRecord(), $this->kategoriDipilih);
        }
    }
}
