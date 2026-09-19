<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    #[On('kode-voucher-diperbarui')]
    public function refreshKodeVoucher(): void
    {
        $this->flushCachedTableRecords();
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
