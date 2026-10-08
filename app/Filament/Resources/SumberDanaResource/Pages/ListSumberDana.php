<?php

namespace App\Filament\Resources\SumberDanaResource\Pages;

use App\Filament\Concerns\CachesResourceListRecords;
use App\Filament\Concerns\HasAuditSaldoAction;
use App\Filament\Resources\AuditSaldoDompetResource;
use App\Filament\Resources\SumberDanaResource;
use App\Services\KuotaAkun;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListSumberDana extends ListRecords
{
    use CachesResourceListRecords;
    use HasAuditSaldoAction;

    protected static string $resource = SumberDanaResource::class;

    public function getSubheading(): Htmlable
    {
        return KuotaAkun::sumberDana(auth()->user());
    }

    protected function resourceListCacheSection(): string
    {
        return 'sumber_dana';
    }

    public function table(Table $table): Table
    {
        return $table
            ->contentGrid(fn (): array => [
                'default' => 2,
                'md' => 2,
                'xl' => ($this->getFilteredTableQuery()?->count() ?? 0) >= 3 ? 3 : 2,
            ])
            ->pushRecordActions([$this->auditSaldoAction()]);
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
                ->modalDescription(fn (): Htmlable => KuotaAkun::sumberDana(auth()->user()))
                ->visible(fn (): bool => auth()->user()->dapatMembuatSumberDana())
                ->before(fn () => abort_unless(auth()->user()->dapatMembuatSumberDana(), 403))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $data['is_default'] = ! auth()->user()->sumberDana()->exists();

                    return $data;
                }),
        ];
    }
}
