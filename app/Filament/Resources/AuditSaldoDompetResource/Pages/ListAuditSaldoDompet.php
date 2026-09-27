<?php

namespace App\Filament\Resources\AuditSaldoDompetResource\Pages;

use App\Filament\Concerns\HasAuditSaldoAction;
use App\Filament\Resources\AuditSaldoDompetResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditSaldoDompet extends ListRecords
{
    use HasAuditSaldoAction;

    protected static string $resource = AuditSaldoDompetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->auditSaldoAction()->label('Tambah audit')->icon('heroicon-o-plus'),
        ];
    }
}
