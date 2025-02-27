<?php

namespace App\Filament\Pages;

use Closure;
use App\Models\BukuKas;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\HasTabs;
use App\Traits\UpdatesFutureTransactions;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class DataTransaksi extends Page implements HasTable, HasForms
{
    use InteractsWithTable,
        InteractsWithForms,
        HasTabs,
        ExposesTableToWidgets,
        UpdatesFutureTransactions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.data-transaksi';

    public $kas_id;
    public $bulan;
    public $tahun;
    public $tahun_awal;
    public $list_kas;
    public $kas_aktif;
    public $id_kas_aktif;
    public $form_action;
    public $saldo_kas;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount()
    {
        $this->bulan = date('n');
        $this->tahun = date('Y');
        $this->tahun_awal = date(
            'Y',
            strtotime(Transaksi::orderBy('tanggal', 'asc')->first()->tanggal)
        );

        if (!in_array($this->activeTab, BukuKas::pluck('nama_buku')->toArray())) {
            $this->activeTab = null;
        }

        $this->list_kas = BukuKas::all();
        $this->kas_aktif = BukuKas::first();
        $this->id_kas_aktif = $this->kas_aktif->id;

        $this->loadDefaultActiveTab();
    }

    protected function getViewData(): array
    {
        return [
            'saldo' => number_format($this->kas_aktif->saldo),
            'total_saldo' => number_format(BukuKas::sum('saldo')),
        ];
    }

    public function editKas($id)
    {
        $this->kas_aktif = BukuKas::find($id);
        $this->dispatch('refresh');
    }

    public function defaultForm($action = null)
    {
        $this->form_action = $action;
        return [
            'buku_kas_id' => $this->id_kas_aktif,
            'tanggal' => date('d M Y, H:i:s')
        ];
    }

    public function refreshTable()
    {
        $this->dispatch('refresh');
    }

    public function form(Form $form): Form
    {
        return $form->schema(Transaksi::form());
    }

    public function table(Table $table): Table
    {
        $query = Transaksi::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->where('buku_kas_id', $this->kas_aktif->id);

        return $table
            ->query($query)
            ->searchPlaceholder('Cari deskripsi...')
            ->paginated([10, 25, 50])
            ->deferLoading()
            ->headerActions([
                Action::make('Transfer saldo')
                    ->tooltip('Transfer saldo ke kas lain')
                    ->action(function ($form, $action, array $data, array $arguments) {
                        DB::transaction(function () use ($data) {
                            $data['user_id'] = auth()->user()->id;
                            $tujuan_id = $data['buku_kas_id_tujuan'];
                            $asal_id = $data['buku_kas_id'];

                            unset($data['buku_kas_id_tujuan']);

                            $transfer_code = uniqid();

                            $data['jenis'] = 'Transfer Pengeluaran';
                            $data['transfer_code'] = $transfer_code;
                            $data['tujuan_buku_tabungan_id'] = $tujuan_id;
                            Transaksi::create($data);

                            $data['jenis'] = 'Transfer Pemasukan';
                            $data['transfer_code'] = $transfer_code;
                            $data['buku_kas_id'] = $tujuan_id;
                            $data['asal_buku_tabungan_id'] = $asal_id;
                            Transaksi::create($data);
                        });

                        Notification::make()
                            ->title('Berhasil Transfer Saldo')
                            ->success()
                            ->send();

                        if ($arguments['another'] ?? false) {
                            $form->fill($this->defaultForm());
                            $action->halt();
                        }

                        $action->cancel();
                    })
                    ->modalWidth(MaxWidth::Large)
                    ->fillForm(fn(): array => $this->defaultForm('transfer'))
                    ->extraModalFooterActions(fn(Action $action): array => [
                        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                            ->label('Tambah yang lain'),
                    ])
                    ->form(Transaksi::form('transfer'))
                    ->color('primary')
                    ->icon('heroicon-o-arrow-path-rounded-square'),

                Action::make('Catat Pemasukan')
                    ->action(function ($form, $action, array $data, array $arguments) {
                        $data['jenis'] = 'Pemasukan';
                        $data['user_id'] = auth()->user()->id;
                        Transaksi::create($data);

                        Notification::make()
                            ->title('Berhasil Catat Pemasukan')
                            ->success()
                            ->send();

                        if ($arguments['another'] ?? false) {
                            $form->fill($this->defaultForm());
                            $action->halt();
                        }

                        $action->cancel();
                    })
                    ->modalWidth(MaxWidth::Large)
                    ->fillForm(fn(): array => $this->defaultForm())
                    ->extraModalFooterActions(fn(Action $action): array => [
                        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                            ->label('Tambah yang lain'),
                    ])
                    ->form(Transaksi::form('pemasukan'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-on-square'),

                Action::make('Catat Pengeluaran')
                    ->action(function (?Transaksi $record, array $data, $form, $action, array $arguments) {
                        $data['jenis'] = 'Pengeluaran';
                        $data['user_id'] = auth()->user()->id;

                        Transaksi::create($data);

                        Notification::make()
                            ->title('Berhasil Catat Pengeluaran')
                            ->success()
                            ->send();

                        if ($arguments['another'] ?? false) {
                            $form->fill($this->defaultForm());
                            $action->halt();
                        }

                        $action->cancel();
                    })
                    ->modalWidth(MaxWidth::Large)
                    ->fillForm(fn(): array => [
                        'buku_kas_id' => $this->id_kas_aktif,
                        'tanggal' => now()
                    ])
                    ->extraModalFooterActions(fn(Action $action): array => [
                        $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                            ->label('Tambah yang lain'),
                    ])
                    ->form(Transaksi::form('pengeluaran'))
                    ->color('danger')
                    ->icon('heroicon-o-arrow-up-on-square'),
            ])
            ->columns([
                IconColumn::make('jenis')
                    ->label('Tipe')
                    ->tooltip(fn($state) => $state)
                    ->icon(fn(string $state): string => match ($state) {
                        'Pemasukan' => 'heroicon-o-arrow-down-on-square',
                        'Pengeluaran' => 'heroicon-o-arrow-up-on-square',
                        'Transfer Pemasukan' => 'heroicon-o-arrow-path-rounded-square',
                        'Transfer Pengeluaran' => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Pemasukan' => 'success',
                        'Pengeluaran' => 'danger',
                        'Transfer Pemasukan' => 'primary',
                        'Transfer Pengeluaran' => 'primary',
                    }),

                TextColumn::make('user.name')
                    ->numeric()
                    ->visible(auth()->user()->isSuper()),

                TextColumn::make('tanggal')
                    ->formatStateUsing(fn($state) => date('d M Y, H:i', strtotime($state))),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->getStateUsing(function ($record) {
                        if (!in_array($record->jenis, ['Transfer Pemasukan', 'Transfer Pengeluaran'])) {
                            $text = $record->jenis_transaksi?->nama_jenis;
                        }

                        if ($record->jenis == 'Transfer Pemasukan') {
                            $text = 'Transfer dari ' . $record->asal_buku_tabungan->nama_buku;
                        }

                        if ($record->jenis == 'Transfer Pengeluaran') {
                            $text = 'Transfer ke ' . $record->tujuan_buku_tabungan->nama_buku;
                        }

                        return $text;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('deskripsi', 'like', "%{$search}%");
                    })
                    ->description(
                        fn($record) => $record->deskripsi
                            ? ('Deskripsi: ' . $record->deskripsi) : ''
                    )
                    ->wrap(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nominal')
                    ->numeric()
                    ->prefix('Rp '),

                TextColumn::make('saldo_akhir')->numeric()
                    ->prefix('Rp '),
            ])
            // ->defaultSort('tanggal', 'desc')
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('tanggal', 'desc')
                    ->orderBy('id', 'desc');
            })
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth(MaxWidth::Large)
                    ->form(Transaksi::form())
                    ->hidden(auth()->user()->isSuper())
                    ->before(function ($record, $livewire) {}),

                DeleteAction::make()
                    ->hidden(auth()->user()->isSuper())
                    ->after(function ($record) {
                        $lastTransaction = Transaksi::where('buku_kas_id', $record->buku_kas_id)
                            ->where('tanggal', '<', $record->tanggal)
                            ->orderBy('tanggal', 'desc')
                            ->first();

                        $isLastTransaction = !Transaksi::where('buku_kas_id', $record->buku_kas_id)
                            ->where('tanggal', '>', $record->tanggal)
                            ->exists();

                        // dd($isLastTransaction && $lastTransaction);
                        if ($isLastTransaction && $lastTransaction) {
                            $record->buku_kas->saldo = $lastTransaction->saldo_akhir;
                            $record->buku_kas->save();
                        } else
                            $this->updateFutureTransactions($record, 'delete');
                    })
            ]);
    }
}
