<?php

namespace App\Filament\Resources\UtangResource\Pages;

use Filament\Tables\Table;
use App\Models\UtangPiutang;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Resources\UtangResource;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms\Components\DateTimePicker;
use App\Models\UtangPiutangDetail as DataModel;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Widgets\UtangPiutangDetailOverview;
use App\Models\UtangPiutangDetail as ModelsUtangPiutangDetail;

class UtangDetail extends Page implements HasTable
{
    use InteractsWithTable, ExposesTableToWidgets;

    protected static string $resource = UtangResource::class;

    protected static string $view = 'filament.resources.utang-resource.pages.utang-detail';

    public $code,
        $parent;

    public function mount($record)
    {
        $this->code = $record;
        $this->parent = UtangPiutang::whereCode($this->code)->first();
    }

    public function getHeaderWidgets(): array
    {
        return [
            UtangPiutangDetailOverview::make([
                'record' => $this->parent->id,
            ])
        ];
    }

    protected function getHeaderActions(): array
    {
        $utang = $this->parent;
        $utang_detail = $utang->utang_piutang_detail->first();

        return [
            ActionsAction::make('ubah')
                ->fillForm(fn(): array => [
                    'kepada' => $utang->kepada,
                    'created_at' => $utang->created_at,
                    'jatuh_tempo' => $utang->tempo ? true : false,
                    'tempo' => $utang->tempo,
                    'nominal' => $utang_detail->nominal,
                    'deskripsi' => $utang->deskripsi,
                ])
                ->modalWidth(MaxWidth::Small)
                ->form(UtangPiutang::formSchema())
                ->action(function ($data) use ($utang_detail, $utang): void {
                    DB::transaction(function () use ($data, $utang_detail, $utang) {
                        $utang->kepada = $data['kepada'];
                        $utang->created_at = $data['created_at'];
                        $utang->tempo = $data['tempo'] ?? null;
                        $utang->deskripsi = $data['deskripsi'];
                        $utang->save();

                        $utang_detail->nominal = $data['nominal'];
                        $utang_detail->created_at = $data['created_at'];
                        $utang_detail->save();

                        Notification::make()
                            ->title('Berhasil mengubah data')
                            ->success()
                            ->send();
                    });
                }),

            ActionsAction::make('hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $tipe = $this->parent->tipe;
                    $this->parent->delete();
                    redirect(url('/admin/' . $tipe . 's'));
                    Notification::make()
                        ->title('Berhasil menghapus data')
                        ->success()
                        ->send();
                })
                ->icon('heroicon-m-trash')
        ];
    }

    public function getTitle(): string | Htmlable
    {
        $data = $this->parent;
        return ucfirst($data->tipe) . ' kepada ' . $data->kepada;
    }

    public function getSubheading(): ?string
    {
        return $this->parent->tempo
            ? 'Jatuh tempo: ' . date('d M Y', strtotime($this->parent->tempo))
            : null;
    }

    public function table(Table $table)
    {
        $utang = $this->parent;

        return $table
            ->headerActions([
                Action::make('tambah')
                    ->label('Tambah Piutang')
                    ->hidden(auth()->user()->isSuper())
                    ->action(
                        fn($data) => ModelsUtangPiutangDetail::action($data, $utang->id, 'tambah')
                    )
                    ->icon('heroicon-o-plus-circle')
                    ->modalWidth(MaxWidth::Small)
                    ->model(ModelsUtangPiutangDetail::class)
                    ->form(ModelsUtangPiutangDetail::form()),

                Action::make('kurang')
                    ->label('Piutang dibayar')
                    ->hidden(auth()->user()->isSuper())
                    ->action(
                        fn($data) => ModelsUtangPiutangDetail::action($data, $utang->id, 'kurang')
                    )
                    ->color('success')
                    ->icon('heroicon-o-minus-circle')
                    ->modalWidth(MaxWidth::Small)
                    ->model(ModelsUtangPiutangDetail::class)
                    ->form(ModelsUtangPiutangDetail::form()),
            ])
            ->query(
                DataModel::whereUtangPiutangId($utang->id)
                    ->selectRaw(
                        "*, SUM(CASE WHEN tipe = 'kurang' THEN -nominal ELSE nominal END) OVER (ORDER BY created_at) as running_balance"
                    )
                    ->latest()
            )
            ->actions([
                Action::make('ubah')
                    ->hiddenLabel()
                    ->tooltip('Ubah')
                    ->modalHeading(
                        fn($record) => 'Ubah '
                            . ucfirst($utang->tipe)
                            . ' '
                            . (ucfirst($record->tipe) == 'tambah' ? 'Ditambah' : 'Dibayar')
                    )
                    ->icon('heroicon-o-pencil')
                    ->fillForm(fn($record): array => [
                        'created_at' => $record->created_at,
                        'nominal' => $record->nominal,
                        'deskripsi' => $record->deskripsi,
                    ])
                    ->modalWidth(MaxWidth::Small)
                    ->form([
                        TextInput::make('nominal')->required(),

                        DateTimePicker::make('created_at')
                            ->default(now())
                            ->maxDate(now())
                            ->label('Tanggal')
                            ->native(false)
                            ->closeOnDateSelection(),

                        Textarea::make('deskripsi'),
                    ])
                    ->action(function ($record, $data) {
                        $record->nominal = $data['nominal'];
                        $record->created_at = $data['created_at'];
                        $record->deskripsi = $data['deskripsi'];
                        $record->save();

                        if ($record->id == $this->parent->utang_piutang_detail->first()->id) {
                            $this->parent->created_at = $data['created_at'];
                            $this->parent->save();
                        }

                        Notification::make()
                            ->title('Berhasil mengubah data')
                            ->success()
                            ->send();
                    })
                    ->hidden(auth()->user()->isSuper()),

                DeleteAction::make()
                    ->hiddenLabel()
                    ->tooltip('Hapus')
                    ->hidden(auth()->user()->isSuper())
            ])
            ->columns([
                IconColumn::make('tipe')
                    ->tooltip(fn(string $state): string => match ($state) {
                        'tambah' => 'Ditambah',
                        'kurang' => 'Dibayar',
                    })
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
