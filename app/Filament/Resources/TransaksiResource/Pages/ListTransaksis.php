<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Models\BukuKas;
use App\Models\Transaksi;
use Filament\Pages\Actions\Action;
use Filament\Forms\Components\Grid;
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
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;
    protected static ?string $navigationLabel = 'Transaksi';


    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()->hidden(auth()->user()->isSuper()),
            Action::make('Catat Pemasukan')
                ->action(function (?Transaksi $record, array $data) {
                    $data['jenis'] = 'Pemasukan';
                    $data['user_id'] = auth()->user()->id;
                    Transaksi::create($data);
                    Notification::make()
                        ->title('Berhasil Catat Pemasukan')
                        // ->body('')
                        ->success()
                        ->send();
                })
                ->fillForm(fn($livewire): array => [
                    'buku_kas_id' => BukuKas::where('nama_buku', $livewire->activeTab)->first()->id,
                    'tanggal' => now()
                ])
                ->form(Transaksi::form()),
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
