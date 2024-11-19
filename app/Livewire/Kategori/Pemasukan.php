<?php

namespace App\Livewire\Kategori;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\JenisTransaksi;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\CreateAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Pemasukan extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions(JenisTransaksi::headerActions('Pemasukan'))
            ->actions(JenisTransaksi::actions('Pemasukan'))
            ->query(
                JenisTransaksi::query()
                    ->whereTipe('Pemasukan')
                    ->orderby('nama_jenis')
            )
            ->columns(JenisTransaksi::columns());
    }

    public function render()
    {
        return view('livewire.kategori.pemasukan');
    }
}
