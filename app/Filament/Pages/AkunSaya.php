<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Models\User;
use App\Models\Wilayah;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;

use function Laravel\Prompts\select;

class AkunSaya extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected string $view = 'filament.pages.akun-saya';
    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

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
                            ->required(),

                        TextInput::make('email')
                            ->required()
                            ->unique()
                            ->email(),

                        TextInput::make('hp')
                            ->tel()->required()
                            ->numeric(),

                        Select::make('provinsi')
                            ->required()
                            ->options(Wilayah::getDaftarProvinsi())
                            ->searchable()->preload()
                            ->live(),

                        Select::make('kota')
                            ->required()
                            ->disabled(fn($get) => ! $get('provinsi'))
                            ->label('Kota/Kabupaten')
                            ->options(function ($get) {
                                if ($get('provinsi')) {
                                    return Wilayah::getDaftarKotaByProvinsi($get('provinsi'));
                                } else return Wilayah::getDaftarKota();
                            })
                            ->searchable(),

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
                            ->disabled(),

                        TextInput::make('masa_aktif')
                            ->visible(fn($get) => $get('type') === 'premium')
                            ->label('Masa aktif akun premium')
                            ->disabled(),

                        TextInput::make('password')
                            ->label('Ubah password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
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
            'email' => $data['email'],
            'hp' => $data['hp'],
            'provinsi' => $data['provinsi'],
            'kota' => $data['kota'],
            'penggunaan' => $data['Penggunaan'],
        ];

        if ($data['password']) {
            $new['password'] = Hash::make($data['password']);
        }

        auth()->user()->update($new);
    }
}
