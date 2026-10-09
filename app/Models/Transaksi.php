<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\TransaksiObserver;
use App\Services\OpsiSelectCache;
use Database\Factories\TransaksiFactory;
use App\Models\SumberDana;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[ScopedBy([UserScope::class])]
#[ObservedBy([TransaksiObserver::class])]
class Transaksi extends Model
{
    /** @use HasFactory<TransaksiFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'transaksi';

    protected $attributes = [
        'pengaruhi_saldo' => true,
    ];

    protected function casts(): array
    {
        return ['pengaruhi_saldo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi): void {
            if ($transaksi->sumber_dana_id || ! $transaksi->user_id) {
                return;
            }

            $sumberDana = SumberDana::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $transaksi->user_id, 'nama_dompet' => 'Cash'],
                ['saldo' => 0, 'is_default' => true, 'jenis' => 'tunai', 'description' => 'Dompet tunai utama']
            );

            $transaksi->sumber_dana_id = $sumberDana->id;
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

    public function sumberDana()
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id')->withTrashed();
    }

    // Alias kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
    public function dompet()
    {
        return $this->sumberDana();
    }

    public function getDompetIdAttribute(): ?int
    {
        return $this->attributes['sumber_dana_id'] ?? null;
    }

    public function setDompetIdAttribute(mixed $value): void
    {
        $this->attributes['sumber_dana_id'] = $value;
    }

    public function getDompetIdTujuanAttribute(): ?int
    {
        return $this->attributes['sumber_dana_id_tujuan'] ?? null;
    }

    public function setDompetIdTujuanAttribute(mixed $value): void
    {
        $this->attributes['sumber_dana_id_tujuan'] = $value;
    }

    public function scopeWhereDompetId($query, mixed $id)
    {
        return $query->where('sumber_dana_id', $id);
    }

    public function labelSumberDanaUntuk(User $user): string
    {
        return $this->sumberDana?->user_id === $user->id
            ? ($this->sumberDana?->nama_dompet ?? '-')
            : 'Sumber dana anggota';
    }

    // Alias kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
    public function labelDompetUntuk(User $user): string
    {
        return $this->labelSumberDanaUntuk($user);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class)->withoutGlobalScopes();
    }

    /** Nama kelompok kategori untuk laporan dan ekspor. */
    public function namaKategoriLaporan(): string
    {
        if (str_starts_with((string) $this->jenis, 'Transfer')) {
            return 'Transfer';
        }

        return $this->kategori?->nama
            ?? ($this->audit_saldo_dompet_detail_id ? 'Audit Saldo' : 'Tanpa kategori');
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
                        ->afterStateUpdated(function (Get $get, Set $set, ?Transaksi $record): void {
                            // Kategori yang tidak terhubung ke kas baru dikosongkan.
                            if (! array_key_exists((int) $get('kategori_id'), static::opsiKategori((int) $get('buku_kas_id'), $record?->jenis))) {
                                $set('kategori_id', null);
                            }
                        })
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

                    Select::make('sumber_dana_id')
                        ->label(fn (?Transaksi $record): string => $record?->tipe_transfer === 'dompet' || $transfer ? 'Sumber dana asal' : 'Sumber dana')
                        ->options(fn (): array => SumberDana::query()->pluck('nama_dompet', 'id')->all())
                        ->disableOptionWhen(fn (string $value): bool => ! array_key_exists($value, static::opsiSumberDanaYangDapatDikelola()))
                        ->required(),

                    Select::make('sumber_dana_id_tujuan')
                        ->label('Sumber dana tujuan')
                        ->options(fn (): array => SumberDana::query()->pluck('nama_dompet', 'id')->all())
                        ->disableOptionWhen(fn (string $value): bool => ! array_key_exists($value, static::opsiSumberDanaYangDapatDikelola()))
                        ->different('sumber_dana_id')
                        ->required()
                        ->visible(fn (?Transaksi $record): bool => $transfer || $record?->tipe_transfer === 'dompet'),

                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->hidden(
                            fn ($record = null) => $transfer || ($record && in_array(
                                $record->jenis,
                                ['Transfer Pemasukan', 'Transfer Pengeluaran']
                            ))
                        )
                        ->options(fn (Get $get, ?Transaksi $record): array => static::opsiKategori((int) $get('buku_kas_id'), $record?->jenis))
                        ->placeholder('Tanpa kategori'),

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
            'sumber_dana_id' => $asal->sumber_dana_id,
            'buku_kas_id_tujuan' => $tujuan->buku_kas_id,
            'sumber_dana_id_tujuan' => $tujuan->sumber_dana_id,
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

    private static function batasiSumberDanaYangDapatDikelola($query)
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

    public static function opsiSumberDanaYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('sumber_dana', fn (): array => static::batasiSumberDanaYangDapatDikelola(SumberDana::query())
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    // Alias kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
    public static function opsiDompetYangDapatDikelola(): array
    {
        return static::opsiSumberDanaYangDapatDikelola();
    }

    public static function opsiSumberDanaSumberTransfer(): array
    {
        return OpsiSelectCache::ingat('sumber_dana', fn (): array => SumberDana::query()
            ->pluck('nama_dompet', 'id')
            ->all(), auth()->id(), 'aktif');
    }

    // Alias kompatibilitas untuk sisa referensi refactor Dompet ke Sumber Dana.
    public static function opsiDompetSumberTransfer(): array
    {
        return static::opsiSumberDanaSumberTransfer();
    }

    public static function opsiBukuKasYangDapatDikelola(): array
    {
        return OpsiSelectCache::ingat('buku-kas', fn (): array => static::batasiBukuKasYangDapatDikelola(BukuKas::query())
            ->pluck('nama_buku', 'id')
            ->all(), auth()->id(), 'dapat-dikelola');
    }

    /** Kategori yang terhubung ke kas tertentu; cache disimpan per kas. */
    public static function opsiKategori(?int $bukuKasId, ?string $tipe = null): array
    {
        // Daftar kategori hanya dibuka untuk kas yang dapat dikelola pengguna.
        if (! $bukuKasId || ! array_key_exists($bukuKasId, static::opsiBukuKasYangDapatDikelola())) {
            return [];
        }

        $tipe = auth()->user()->pisahkanTipeKategori() && in_array($tipe, ['Pemasukan', 'Pengeluaran'], true)
            ? $tipe
            : 'semua';

        return OpsiSelectCache::ingat('kategori', fn (): array => Kategori::withoutGlobalScopes()
            ->whereIn('id', DB::table('kategori_kas')->where('buku_kas_id', $bukuKasId)->select('kategori_id'))
            // Kategori bertipe Semua selalu ikut tampil walau dropdown difilter sesuai tipe transaksi.
            ->when($tipe !== 'semua', fn ($query) => $query->whereIn('tipe', [$tipe, 'Semua']))
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all(), $bukuKasId, $tipe);
    }
}
