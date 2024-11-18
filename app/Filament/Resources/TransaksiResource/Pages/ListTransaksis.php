<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Models\BukuKas;
use App\Models\Transaksi;
use Illuminate\Support\Arr;
use Filament\Pages\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TransaksiResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;

class ListTransaksis extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransaksiResource::class;
    protected static ?string $navigationLabel = 'Transaksi';

    public function defaultForm($livewire)
    {
        return [
            'buku_kas_id' => BukuKas::where('nama_buku', $livewire->activeTab)->first()->id,
            'tanggal' => date('d M Y, H:i:s')
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Transfer saldo')
                ->tooltip('Transfer saldo ke kas lain')
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    DB::transaction(function () use ($data) {
                        $data['user_id'] = auth()->user()->id;
                        $tujuan_id = $data['buku_kas_id_tujuan'];
                        $asal_id = $data['buku_kas_id'];

                        unset($data['buku_kas_id_tujuan']);

                        $data['jenis'] = 'Transfer Pengeluaran';
                        $data['tujuan_buku_tabungan_id'] = $tujuan_id;
                        Transaksi::create($data);

                        $data['jenis'] = 'Transfer Pemasukan';
                        $data['buku_kas_id'] = $tujuan_id;
                        $data['asal_buku_tabungan_id'] = $asal_id;
                        Transaksi::create($data);
                    });

                    Notification::make()
                        ->title('Berhasil Transfer Saldo')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn($livewire): array => $this->defaultForm($livewire))
                ->extraModalFooterActions(fn(Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form(true))
                ->color('primary')
                ->icon('heroicon-o-arrow-path-rounded-square'),

            Action::make('Catat Pemasukan')
                ->action(function ($form, $action, $livewire, array $data, array $arguments) {
                    $data['jenis'] = 'Pemasukan';
                    $data['user_id'] = auth()->user()->id;
                    Transaksi::create($data);

                    Notification::make()
                        ->title('Berhasil Catat Pemasukan')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn($livewire): array => $this->defaultForm($livewire))
                ->extraModalFooterActions(fn(Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form())
                ->color('success')
                ->icon('heroicon-o-arrow-down-on-square'),

            Action::make('Catat Pengeluaran')
                ->action(function (?Transaksi $record, array $data, $livewire, $form, $action, array $arguments) {
                    $data['jenis'] = 'Pengeluaran';
                    $data['user_id'] = auth()->user()->id;
                    $data['tanggal'] = date('d M Y, H:i:s', strtotime($data['tanggal']));

                    Transaksi::create($data);
                    Notification::make()
                        ->title('Berhasil Catat Pengeluaran')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill($this->defaultForm($livewire));
                        $action->halt();
                    }

                    $action->cancel();
                })
                ->fillForm(fn($livewire): array => [
                    'buku_kas_id' => BukuKas::where('nama_buku', $livewire->activeTab)->first()->id,
                    'tanggal' => now()
                ])
                ->extraModalFooterActions(fn(Action $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('Tambah yang lain'),
                ])
                ->form(Transaksi::form())
                ->color('danger')
                ->icon('heroicon-o-arrow-up-on-square'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            KasOverview::class
        ];
    }

    public function getTabs(): array
    {
        $kas = BukuKas::all();
        // dd($kas);

        $tab = [];
        foreach ($kas as $key => $value) {
            $tab[$value->nama_buku] = Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('buku_kas_id', $value->id));
        }
        // return [
        //     'all' => Tab::make(),
        //     'active' => Tab::make()
        //         ->modifyQueryUsing(fn(Builder $query) => $query->where('active', true)),
        //     'inactive' => Tab::make()
        //         ->modifyQueryUsing(fn(Builder $query) => $query->where('active', false)),
        // ];

        return $tab;
    }
}
