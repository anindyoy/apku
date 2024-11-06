<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use Filament\Actions;
use App\Models\BukuKas;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TransaksiResource;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->hidden(auth()->user()->isSuper()),
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
