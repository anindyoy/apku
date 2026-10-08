<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Filament\Concerns\HidesFromAdminNavigation;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AkunSaya extends Page implements HasForms
{
    use HidesFromAdminNavigation;
    use InteractsWithForms;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Akun Saya';

    protected string $view = 'filament.pages.akun-saya';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('Nama lengkap Anda'),

                        TextInput::make('email')
                            ->disabled(),

                        TextInput::make('hp')
                            ->tel()->required()
                            ->numeric()
                            ->placeholder('Contoh: 0812-3456-7890'),

                        Textarea::make('alamat')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Alamat lengkap (opsional)'),

                        Select::make('penggunaan')
                            ->options([
                                'Pribadi/Keluarga' => 'Pribadi/Keluarga',
                                'Unit Usaha' => 'Unit Usaha',
                                'Organisasi/Komunitas' => 'Organisasi/Komunitas',
                                'Perusahaan' => 'Perusahaan',
                            ])
                            ->required(),

                        TextInput::make('type')
                            ->label('Tipe akun')
                            ->formatStateUsing(fn ($state) => filled($state) ? ucfirst($state) : null)
                            ->disabled(),

                        TextInput::make('masa_aktif')
                            ->visible(fn ($get) => strtolower((string) $get('type')) === 'premium')
                            ->label('Masa aktif akun premium')
                            ->formatStateUsing(fn ($state) => filled($state) ? Carbon::parse($state)->format('Y-m-d') : null)
                            ->disabled(),

                        TextInput::make('password')
                            ->label('Ubah password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->placeholder('Kosongkan jika tidak ingin mengubah password'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $new = [
            'name' => $data['name'],
            'hp' => $data['hp'],
            'alamat' => $data['alamat'],
            'penggunaan' => $data['penggunaan'],
        ];

        // Password sudah di-hash oleh dehydrateStateUsing di form field
        if (! empty($data['password'])) {
            $new['password'] = $data['password'];
        }

        auth()->user()->update($new);
    }
}
