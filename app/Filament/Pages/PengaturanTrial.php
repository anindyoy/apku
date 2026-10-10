<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Pengaturan;
use App\Services\PengaturanTrial as LayananTrial;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

// Saklar dan durasi trial Premium untuk admin tanpa deploy ulang.
class PengaturanTrial extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Pengaturan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $title = 'Trial Premium';

    // Label submenu dibedakan dari resource Trial Premium agar tidak bentrok di navbar.
    protected static ?string $navigationLabel = 'Pengaturan Trial';

    protected string $view = 'filament.pages.setting';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(app(LayananTrial::class)->semua());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Coba gratis Premium')
                ->description('Matikan saklar untuk menutup pendaftaran trial baru tanpa mengganggu trial yang sedang berjalan.')
                ->schema([
                    Toggle::make('aktif')->label('Program trial aktif')->default(true),
                    TextInput::make('durasi_hari')->label('Durasi trial')
                        ->suffix('hari')->numeric()->integer()->minValue(1)->maxValue(90)->required(),
                ])->columns(2),
        ])->statePath('data');
    }

    public function simpan(): void
    {
        abort_unless(static::canAccess(), 403);
        app(LayananTrial::class)->simpan(auth()->user(), $this->form->getState());
        Notification::make()->title('Pengaturan trial disimpan')->success()->send();
    }
}
