<?php

namespace App\Filament\Resources\UtangResource\Pages;

use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use App\Filament\Resources\UtangResource;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\UtangPiutangDetail as DataModel;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Widgets\UtangPiutangDetailOverview;
use App\Models\UtangPiutangDetail as ModelsUtangPiutangDetail;

class UtangPiutangDetail extends Page implements HasTable
{
    use InteractsWithTable, ExposesTableToWidgets;

    protected static string $resource = UtangResource::class;

    protected static string $view = 'filament.resources.utang-resource.pages.utang-piutang-detail';

    public $record,
        $activeTab;

    public function mount($record)
    {
        $this->record = $record;
    }

    public function getHeaderWidgets(): array
    {
        return [
            UtangPiutangDetailOverview::make([
                'record' => $this->record,
            ])
        ];
    }

    public function getTitle(): string | Htmlable
    {
        $data = UtangPiutang::find($this->record);
        return ucfirst($data->tipe) . ' kepada ' . $data->kepada;
    }

    public function table(Table $table)
    {
        return $table
            ->headerActions([
                Action::make('tambah')
                    ->label('Tambah Piutang')
                    ->hidden(auth()->user()->isSuper())
                    ->action(
                        fn($data) => ModelsUtangPiutangDetail::action($data, $this->record, 'tambah')
                    )
                    ->icon('heroicon-o-plus-circle')
                    ->modalWidth(MaxWidth::Small)
                    ->model(ModelsUtangPiutangDetail::class)
                    ->form(ModelsUtangPiutangDetail::form()),

                Action::make('kurang')
                    ->label('Piutang dibayar')
                    ->hidden(auth()->user()->isSuper())
                    ->action(
                        fn($data) => ModelsUtangPiutangDetail::action($data, $this->record, 'kurang')
                    )
                    ->color('success')
                    ->icon('heroicon-o-minus-circle')
                    ->modalWidth(MaxWidth::Small)
                    ->model(ModelsUtangPiutangDetail::class)
                    ->form(ModelsUtangPiutangDetail::form()),
            ])
            ->query(
                DataModel::whereUtangPiutangId($this->record)
                    ->selectRaw(
                        "*, SUM(CASE WHEN tipe = 'kurang' THEN -nominal ELSE nominal END) OVER (ORDER BY created_at) as running_balance"
                    )
                    ->latest()
            )
            ->columns([
                IconColumn::make('tipe')
                    ->tooltip(fn($state) => $state)
                    ->icon(fn(string $state): string => match ($state) {
                        'tambah' => 'heroicon-o-plus-circle',
                        'kurang' => 'heroicon-o-minus-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'tambah' => 'success',
                        'kurang' => 'danger',
                    }),

                TextColumn::make('created_at')->date('d M Y, H:i')
                    ->label('Tanggal'),

                TextColumn::make('nominal')
                    ->numeric()
                    ->prefix('Rp '),

                TextColumn::make('deskripsi')->wrap(),

                TextColumn::make('running_balance')
                    ->label('Sisa')
                    ->numeric()
                    ->prefix('Rp '),
            ]);
    }
}
