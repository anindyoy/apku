<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\TransaksiObserver;
use App\Services\OpsiSelectCache;
use Database\Factories\TransaksiFactory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserScope::class])]
#[ObservedBy([TransaksiObserver::class])]
class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'transaksi';

    protected function casts(): array
    {
        return ['pengaruhi_saldo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi): void {
            if ($transaksi->dompet_id || ! $transaksi->user_id) {
                return;
            }

            $dompet = Dompet::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $transaksi->user_id, 'nama_dompet' => 'Cash'],
                ['saldo' => 0, 'is_default' => true, 'description' => 'Dompet tunai utama']
            );

            $transaksi->dompet_id = $dompet->id;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku_kas()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function dompet()
    {
        return $this->belongsTo(Dompet::class)->withTrashed();
    }

    public function labelDompetUntuk(User $user): string
    {
        return $this->dompet?->user_id === $user->id
            ? ($this->dompet?->nama_dompet ?? '-')
            : 'Dompet anggota';
    }

    public function jenis_transaksi()
    {
        return $this->belongsTo(JenisTransaksi::class)->withoutGlobalScopes();
    }

    public function import_transaksi()
    {
        return $this->belongsTo(ImportTransaksi::class);
    }

    public function detailAuditSaldo()
    {
        return $this->belongsTo(AuditSaldoDompetDetail::class, 'audit_saldo_dompet_detail_id');
    }

    public function transaksiEmas()
    {
        return $this->hasOne(TransaksiEmas::class);
    }

    public function tujuan_buku_tabungan()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public function asal_buku_tabungan()
    {
        return $this->belongsTo(BukuKas::class);
    }

    public static function form($transfer = false)
    {
        return [
            Grid::make(['default' => 1, 'sm' => 2])
                ->schema([
                    Select::make('jenis')
                        ->options([
                            'Pemasukan' => 'Pemasukan',
                            'Pengeluaran' => 'Pengeluaran',
                            'Transfer Pemasukan' => 'Transfer Pemasukan',
                            'Transfer Pengeluaran' => 'Transfer Pengeluaran',
                        ])
                        ->disabled()
                        ->visible(fn ($record) => $record),

                    Select::make('buku_kas_id')
                        ->label(fn (?Transaksi $record): string => $record?->transfer_code && $record->tipe_transfer !== 'dompet' ? 'Kas asal' : 'Kas')
                        ->live()
                        ->options(fn (): array => BukuKas::query()->pluck('nama_buku', 'id')->all())
                        ->disableOptionWhen(fn (string $value, ?Transaksi $record): bool => ! array_key_exists($value, static::opsiBukuKasYangDapatDikelola())
                            || (filled($record?->transfer_code) && BukuKas::find($value)?->user_id !== auth()->id()))
                        ->required(),

                    Select::make('buku_kas_id_tujuan')
                        ->label('Kas tujuan')
                        ->options(fn (): array => BukuKas::query()->pluck('nama_buku', 'id')->all())
                        ->disableOptionWhen(fn (string $value): bool => ! array_key_exists($value, static::opsiBukuKasYangDapatDikelola())
                            || BukuKas::find($value)?->user_id !== auth()->id())
                        ->different('buku_kas_id')
                        ->required()
                        ->visible(fn (?Transaksi $record): bool => (bool) $record?->transfer_code && $record->tipe_transfer !== 'dompet'),

                    Select::make('dompet_id')
                        ->label(fn (?Transaksi $record): string => $record?->tipe_transfer === 'dompet' || $transfer ? 'Dompet asal' : 'Dompet')
                        ->options(fn (): array => Dompet::query()->pluck('nama_dompet', 'id')->all())
                        ->disableOptionWhen(fn (string $value): bool => ! array_key_exists($value, static::opsiDompetYangDapatDikelola()))
                        ->required(),

                    Select::make('dompet_id_tujuan')
                        ->label('Dompet tujuan')
                        ->options(fn (): array => Dompet::query()->pluck('nama_dompet', 'id')->all())
                        ->disableOptionWhen(fn (string $value): bool => ! array_key_exists($value, static::opsiDompetYangDapatDikelola()))
                        ->different('dompet_id')
                        ->required()
                        ->visible(fn (?Transaksi $record): bool => $transfer || $record?->tipe_transfer === 'dompet'),

                    Select::make('jenis_transaksi_id')
                        ->label('Aktivitas')
                        ->hidden(
                            fn ($record = null) => $transfer || ($record && in_array(
                                $record->jenis,
                                ['Transfer Pemasukan', 'Transfer Pengeluaran']
                            ))
                        )
                        ->options(fn (?Transaksi $record): array => static::opsiJenisTransaksi($record?->jenis))
                        ->required(),

                    DateTimePicker::make('tanggal')
                        ->required()
                        ->seconds(false)
                        ->native(false)
                        ->closeOnDateSelection()
                        ->displayFormat('d M Y, H:i')
                        ->maxDate(now()),

                    TextInput::make('nominal')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),

                    TextInput::make('deskripsi')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function dataFormUbah(Transaksi $record): array
    {
        $data = $record->attributesToArray();

        if (! $record->transfer_code) {
            return $data;
        }

        $pasangan = static::withoutGlobalScopes()
            ->where('transfer_code', $record->transfer_code)
            ->get()
            ->keyBy('jenis');
        $asal = $pasangan->get('Transfer Pengeluaran');
        $tujuan = $pasangan->get('Transfer Pemasukan');

        if (! $asal || ! $tujuan) {
            return $data;
        }

        return array_replace($data, [
            'buku_kas_id' => $asal->buku_kas_id,
            'dompet_id' => $asal->dompet_id,
            'buku_kas_id_tujuan' => $tujuan->buku_kas_id,
            'dompet_id_tujuan' => $tujuan->dompet_id,
        ]);
    }

    private static function batasiBukuKasYangDapatDikelola($query)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($query) use ($user): void {
            if ($user->masaAktifBerlaku()) {
                $query->where('buku_kas.user_id', $user->id);
            } else {
                $query->whereKey(array_filter([
                    $user->idBukuKasUtama(),
                    $user->idBukuKasTambahanGratis(),
                ]));
            }

            $query->orWhereHas('shares', fn ($query) => $query
                ->aktif()
                ->where('user_id', $user->id)
                ->where('privilege', 'editor'));
        });
    }

    private static function batasiDompetYangDapatDikelola($query)
    {
        $user = auth()->user();
        $query->withoutTrashed();

        if ($user->isAdmin() || $user->masaAktifBerlaku()) {
            return $query;
        }

        return $query->whereKey(array_filter([
            $user->idDompetUtama(),
            $user->idDompetTambahanGratis(),
        ]));
    }

    public static function opsiDompetYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('dompet', fn (): array => static::batasiDompetYangDapatDikelola(Dompet::query())
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    public static function opsiDompetSumberTransfer(): array
    {
        return OpsiSelectCache::ingat('dompet', fn (): array => Dompet::query()
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'aktif');
    }

    public static function opsiBukuKasYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('buku-kas', fn (): array => static::batasiBukuKasYangDapatDikelola(BukuKas::query())
            ->pluck('nama_buku', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    public static function opsiJenisTransaksi(?string $tipe = null): array
    {
        $tipe = in_array($tipe, ['Pemasukan', 'Pengeluaran'], true) ? $tipe : 'semua';

        if ($tipe === 'semua') {
            return array_replace(
                static::opsiJenisTransaksi('Pemasukan'),
                static::opsiJenisTransaksi('Pengeluaran'),
            );
        }

        return OpsiSelectCache::ingat('jenis-transaksi', fn (): array => JenisTransaksi::query()
            ->where('tipe', $tipe)
            ->orderBy('nama_jenis')
            ->pluck('nama_jenis', 'id')
            ->all(), auth()->id(), $tipe);
    }
}
