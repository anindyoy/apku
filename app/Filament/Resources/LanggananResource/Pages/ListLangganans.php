<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Concerns\HasMulaiTrialPremiumAction;
use App\Filament\Resources\LanggananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListLangganans extends ListRecords
{
    use HasMulaiTrialPremiumAction;

    protected static string $resource = LanggananResource::class;

    protected string $view = 'filament.resources.langganan-resource.pages.list-langganans';

    // Menyegarkan tabel riwayat setelah komponen pilih paket membuat order baru.
    #[On('order-langganan-dibuat')]
    public function refreshSetelahOrderBaru(): void
    {
        $this->flushCachedTableRecords();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat order')
                ->visible(fn (): bool => ! auth()->user()->isAdmin()),
        ];
    }
}
