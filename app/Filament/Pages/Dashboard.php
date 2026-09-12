<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasTambahTransaksiAction;
use App\Filament\Resources\TransaksiResource;
use App\Filament\Widgets\AdminOverview;
use App\Models\Transaksi;
use App\Services\DashboardCache;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Dashboard extends BaseDashboard implements HasTable
{
    use HasTambahTransaksiAction;
    use InteractsWithTable;

    public ?string $filterBukuKas = null;

    public ?string $filterDompet = null;

    protected static ?string $navigationLabel = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    public const SECTIONS = [
        'kas' => 'Kas saya',
        'dompet' => 'Dompet saya',
        'utang' => 'Utang',
        'piutang' => 'Piutang',
        'langganan' => 'Masa aktif langganan',
        'transaksi' => '5 transaksi terakhir',
    ];

    public function getTitle(): string|Htmlable
    {
        return auth()->user()->isAdmin() ? 'Dashboard Admin' : 'Dashboard';
    }

    public function getWidgets(): array
    {
        return auth()->user()->isAdmin() ? [AdminOverview::class] : [];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public function sections(): array
    {
        $saved = DashboardCache::remember('settings', auth()->id(), fn () => auth()->user()->dashboard_settings ?? []);
        $sections = [];
        foreach ($saved as $item) {
            if (is_array($item) && isset(self::SECTIONS[$item['key'] ?? ''])) {
                $sections[$item['key']] = ['key' => $item['key'], 'visible' => (bool) ($item['visible'] ?? true)];
            }
        }
        foreach (self::SECTIONS as $key => $label) {
            $sections[$key] ??= ['key' => $key, 'visible' => true];
        }

        return array_values($sections);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aturDashboard')
                ->label('Atur dashboard')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (): bool => ! auth()->user()->isAdmin())
                ->modalHeading('Atur tampilan dashboard')
                ->modalSubmitActionLabel('Simpan pengaturan')
                ->schema([
                    Repeater::make('sections')
                        ->label('Bagian dashboard')
                        ->helperText('Geser bagian atau gunakan tombol urutan. Matikan toggle untuk menyembunyikannya.')
                        ->schema([
                            Select::make('key')->label('Bagian')->options(self::SECTIONS)->disabled()->dehydrated()->required(),
                            Toggle::make('visible')->label('Tampilkan')->default(true),
                        ])
                        ->columns(2)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderableWithButtons()
                        ->itemLabel(fn (array $state): string => self::SECTIONS[$state['key'] ?? ''] ?? 'Bagian'),
                ])
                ->fillForm(fn (): array => ['sections' => $this->sections()])
                ->action(fn (array $data) => $this->saveSettings($data['sections'])),
        ];
    }

    public function tambahTransaksiAction(): Action
    {
        return $this->buatAksiTambahTransaksi()
            ->name('tambahTransaksi')
            ->label('Tambah')
            ->visible(fn (): bool => ! auth()->user()->isAdmin() && $this->memilikiBukuKasYangDapatDikelola())
            ->after(fn () => $this->resetTable());
    }

    public function saveSettings(array $sections): void
    {
        abort_if(auth()->user()->isAdmin(), 403);
        $data = Validator::make(['sections' => array_values($sections)], [
            'sections' => ['required', 'array', 'size:6'],
            'sections.*' => ['required', 'array:key,visible'],
            'sections.*.key' => ['required', 'string', 'distinct', Rule::in(array_keys(self::SECTIONS))],
            'sections.*.visible' => ['required', 'boolean'],
        ])->validate();

        auth()->user()->forceFill(['dashboard_settings' => $data['sections']])->save();
        Notification::make()->title('Pengaturan dashboard disimpan')->success()->send();
    }

    public function sectionData(string $key): mixed
    {
        abort_if(auth()->user()->isAdmin(), 403);
        $user = auth()->user();

        if (! in_array($key, ['kas', 'dompet', 'utang', 'piutang', 'langganan'], true)) {
            return null;
        }

        return DashboardCache::remember($key, $user->id, fn () => match ($key) {
            'kas' => $user->buku_kas()->get(),
            'dompet' => $user->dompet()->get(),
            'utang', 'piutang' => $this->debtData($key),
            'langganan' => [
                'active' => $user->masaAktifBerlaku(),
                'expires' => $user->masa_aktif?->translatedFormat('d F Y'),
                'days' => $user->masaAktifBerlaku() ? (int) today()->diffInDays($user->masa_aktif) : 0,
            ],
            default => null,
        });
    }

    private function debtData(string $type): array
    {
        $query = auth()->user()->utang_piutang()->where('tipe', $type)
            ->selectRawNominalAndLastActivityDate();

        return [
            'total' => (clone $query)->get()->sum('nominal'),
            'latest' => $query->orderByDesc('created_at')->orderByDesc('id')->limit(3)->get(),
        ];
    }

    public function getTableRecords(): Collection
    {
        return DashboardCache::remember('transaksi', auth()->id(), fn () => $this->getFilteredSortedTableQuery()->get());
    }

    public function table(Table $table): Table
    {
        $columns = array_filter(TransaksiResource::transactionColumns(), fn ($column): bool => ! in_array($column->getName(), ['created_at', 'updated_at'], true));
        foreach ($columns as $column) {
            $column->searchable(false)->sortable(false);
        }

        return $table
            ->query(Transaksi::query()
                ->where('user_id', auth()->id())
                ->when(auth()->user()->isAdmin(), fn ($query) => $query->whereRaw('1 = 0'))
                ->with(['user', 'buku_kas', 'dompet', 'jenis_transaksi', 'asal_buku_tabungan', 'tujuan_buku_tabungan'])
                ->orderByDesc('tanggal')->orderByDesc('id')->limit(5))
            ->columns(array_values($columns))
            ->recordActions(TransaksiResource::transactionActions())
            ->paginated(false)
            ->recordUrl(null)
            ->recordAction(null)
            ->emptyStateHeading('Belum ada transaksi');
    }
}
