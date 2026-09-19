<?php

namespace App\Filament\Resources\VoucherResource\RelationManagers;

use App\Models\VoucherCode;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CodesRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'codes';

    protected static ?string $title = 'Kelola kode voucher';

    protected string $view = 'filament.voucher-codes-manager';

    public string $kodeBaru = '';

    public function tambahKode(): void
    {
        Gate::authorize('update', $this->getOwnerRecord());
        Gate::authorize('create', VoucherCode::class);

        $this->kodeBaru = Str::upper(trim($this->kodeBaru));
        $this->validate([
            'kodeBaru' => ['required', 'string', 'max:255', 'unique:voucher_codes,code'],
        ], [], ['kodeBaru' => 'kode voucher']);

        $this->getOwnerRecord()->codes()->create(['code' => $this->kodeBaru]);
        $this->reset('kodeBaru');
        $this->resetValidation('kodeBaru');
        $this->resetTable();
        $this->dispatch('kode-voucher-diperbarui');
        Notification::make()->title('Kode voucher berhasil dibuat')->success()->send();
    }

    public function mount(): void
    {
        Gate::authorize('update', $this->getOwnerRecord());
        parent::mount();
    }

    public function hydrate(): void
    {
        Gate::authorize('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state): string => Str::upper(trim($state))),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->recordTitleAttribute('code')
            ->modelLabel('Kode voucher')
            ->pluralModelLabel('Kode voucher')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->copyable(),
                TextColumn::make('langganans_count')->counts('langganans')->label('Jumlah pemakaian'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->after(fn () => $this->dispatch('kode-voucher-diperbarui')),
            ]);
    }
}
