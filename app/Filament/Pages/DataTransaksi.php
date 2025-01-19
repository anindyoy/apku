<?php

namespace App\Filament\Pages;

use App\Models\BukuKas;
use Filament\Pages\Page;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Resources\Concerns\HasTabs;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Resources\TransaksiResource\Widgets\KasOverview;

class DataTransaksi extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms, HasTabs, ExposesTableToWidgets;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.data-transaksi';

    public $kas_id;
    public $bulan;
    public $tahun;
    public $tahun_awal;
    public $list_kas;
    public $kas_aktif;
    public $id_kas_aktif;
    public $saldo_kas;

    protected $listeners = [
        'refresh' => '$refresh'
    ];

    public function mount()
    {
        $this->bulan = date('m');
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
        // dd(BukuKas::sum('saldo'));
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

    protected function getHeaderWidgets(): array
    {
        return [
            // KasOverview::class,
        ];
    }

    // public function updatedKasAktif()
    // {
    //     dd($this->kas_aktif);
    //     $this->resetPage();
    // }

    public function table(Table $table): Table
    {
        // $query = Transaksi::query()->select(
        //     'transaksi.*',
        //     DB::raw(
        //         '(CASE
        //         WHEN ROW_NUMBER() OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) = 1
        //         THEN (SELECT saldo FROM buku_kas WHERE id = transaksi.buku_kas_id)
        //         ELSE (SELECT saldo FROM buku_kas WHERE id = transaksi.buku_kas_id) +
        //             SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING)
        //     END) as saldo'
        //     )
        // )
        //     ->whereMonth('tanggal', $this->bulan)
        //     ->whereYear('tanggal', $this->tahun)
        //     ->where('buku_kas_id', $this->kas_aktif->id);

        return $table
            // ->query(
            //     fn() => Transaksi::query()->select(
            //         'transaksi.*',
            //         DB::raw(
            //             // 'SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) as saldo'
            //             '(CASE
            //             WHEN ROW_NUMBER() OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc) = 1
            //             THEN (SELECT saldo FROM buku_kas WHERE id = transaksi.buku_kas_id)
            //             ELSE (SELECT saldo FROM buku_kas WHERE id = transaksi.buku_kas_id) +
            //                 SUM(CASE WHEN jenis in ("Pemasukan", "Transfer Pemasukan") THEN nominal ELSE -nominal END) OVER (PARTITION BY buku_kas_id ORDER BY tanggal, id desc ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING)
            //         END) as saldo'
            //         )
            //     )
            // )
            // ->modifyQueryUsing(
            //     fn(Builder $query) => $query
            //         ->whereMonth('tanggal', $this->bulan)
            //         ->whereYear('tanggal', $this->tahun)
            //         ->where('buku_kas_id', $this->kas_aktif->id)
            // )
            ->query($query)
            ->searchPlaceholder('Cari deskripsi...')
            ->paginated([10, 25, 50])
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

                TextColumn::make('saldo')->numeric()
                    ->prefix('Rp '),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->hidden(auth()->user()->isSuper())
                    ->before(function ($record, $livewire) {
                        if (in_array($record->jenis, ['Transfer Pemasukan', 'Transfer Pengeluaran'])) {
                            $relatedTransactions = Transaksi::where('transfer_code', $record->transfer_code)->get();
                            $nominal_baru = $livewire->mountedTableActionsData[0]['nominal'];
                            $selisih = $nominal_baru - $record->nominal;

                            foreach ($relatedTransactions as $relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo -= $selisih;
                                } else if ($relatedTransaction->jenis === 'Transfer Pemasukan') {
                                    $relatedKas->saldo += $selisih;
                                }

                                $relatedKas->save();

                                if ($relatedTransaction->id != $record->id) {
                                    $relatedTransaction->nominal = $nominal_baru;
                                    $relatedTransaction->save();
                                }
                            }
                        }
                    }),

                DeleteAction::make()
                    ->hidden(auth()->user()->isSuper())
                    ->after(function ($record) {
                        $kas = $record->buku_kas;
                        if (in_array($record->jenis, ['Transfer Pengeluaran', 'Transfer Pemasukan'])) {
                            $kas = $record->buku_kas;

                            if ($record->jenis === 'Transfer Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } else if ($record->jenis === 'Transfer Pemasukan') {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();

                            $relatedTransaction = Transaksi::where('transfer_code', $record->transfer_code)->first();

                            if ($relatedTransaction) {
                                $relatedKas = $relatedTransaction->buku_kas;

                                if ($relatedTransaction->jenis === 'Transfer Pengeluaran') {
                                    $relatedKas->saldo += $relatedTransaction->nominal;
                                } else
                                    $relatedKas->saldo -= $relatedTransaction->nominal;

                                $relatedKas->save();
                                $relatedTransaction->delete();
                            }
                        } else if (in_array($record->jenis, ['Pengeluaran', 'Pemasukan'])) {
                            if ($record->jenis == 'Pengeluaran') {
                                $kas->saldo = $kas->saldo + $record->nominal;
                            } else {
                                $kas->saldo = $kas->saldo - $record->nominal;
                            }

                            $kas->save();
                        }
                    })
            ]);
    }
}
