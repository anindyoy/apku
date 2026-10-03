<?php

namespace App\Livewire\Kategori;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Kategori;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Pengeluaran extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    // Add required Livewire properties for Filament Actions
    public array $mountedActions = [];
    public array $mountedAction = [];

    // Add required methods for Filament Actions
    public function cacheMountedActions(): void
    {
        // Required for Filament Actions compatibility
    }

    public function getOriginallyMountedActionIndex(): ?int
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions(Kategori::headerActions('Pengeluaran'))
            ->actions(Kategori::actions('Pengeluaran'))
            ->query(
                Kategori::query()
                    ->whereTipe('Pengeluaran')
                    ->orderby('nama')
            )
            ->columns(Kategori::columns());
    }

    public function render()
    {
        return view('livewire.kategori.pengeluaran');
    }
}
