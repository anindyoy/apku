<?php

namespace App\Filament\Resources\DompetResource\Pages;

use App\Filament\Concerns\CachesResourceListRecords;
use App\Filament\Concerns\HasAuditSaldoAction;
use App\Filament\Resources\AuditSaldoDompetResource;
use App\Filament\Resources\DompetResource;
use App\Services\KuotaAkun;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListDompet extends ListRecords
{
    use CachesResourceListRecords;
    use HasAuditSaldoAction;

    protected static string $resource = DompetResource::class;

    public function getSubheading(): Htmlable
    {
        return KuotaAkun::dompet(auth()->user());
    }

    protected function resourceListCacheSection(): string
    {
        return 'dompet';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->auditSaldoAction(),
            Action::make('riwayatAudit')
                ->label('Riwayat audit')
                ->icon('heroicon-o-clock')
                ->url(AuditSaldoDompetResource::getUrl()),
            CreateAction::make()
                ->modalDescription(fn (): Htmlable => KuotaAkun::dompet(auth()->user()))
                ->visible(fn (): bool => auth()->user()->dapatMembuatDompet())
                ->before(fn () => abort_unless(auth()->user()->dapatMembuatDompet(), 403))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $data['is_default'] = ! auth()->user()->dompet()->exists();

                    return $data;
                }),
        ];
    }
}
