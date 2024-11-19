<?php

namespace App\Livewire\Kategori;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\JenisTransaksi;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Pengeluaran extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions(JenisTransaksi::headerActions('Pengeluaran'))
            ->actions(JenisTransaksi::actions('Pengeluaran'))
            ->query(
                JenisTransaksi::query()
                    ->whereTipe('Pengeluaran')
                    ->orderby('nama_jenis')
            )
            ->columns(JenisTransaksi::columns());
    }

    public function render()
    {
        return view('livewire.kategori.pengeluaran');
    }
}
