<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;

use function Laravel\Prompts\select;

class AkunSaya extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Akun Saya';
    protected string $view = 'filament.pages.akun-saya';
    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';
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
                            ->required(),

                        TextInput::make('email')
                            ->required()
                            ->unique()
                            ->email(),

                        TextInput::make('hp')
                            ->tel()->required()
                            ->numeric(),

                        Textarea::make('alamat')
                            ->rows(3)
                            ->columnSpanFull(),

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
