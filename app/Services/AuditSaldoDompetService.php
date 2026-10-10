<?php

namespace App\Services;

use App\Models\AuditSaldoDompet;
use App\Models\AuditSaldoDompetDetail;
use App\Models\BukuKas;
use App\Models\SumberDana;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditSaldoDompetService
{
    private const PECAHAN_UANG = [
        'Uang Kertas' => [
            'kertas_100000' => 100000,
            'kertas_50000' => 50000,
            'kertas_20000' => 20000,
            'kertas_10000' => 10000,
            'kertas_5000' => 5000,
            'kertas_2000' => 2000,
            'kertas_1000' => 1000,
        ],
        'Uang Logam' => [
            'logam_1000' => 1000,
            'logam_500' => 500,
            'logam_200' => 200,
            'logam_100' => 100,
        ],
    ];

    /**
     * @param  array<int, array{dompet_id: int, saldo_aplikasi: int, saldo_riil: int, catatan?: string|null, jumlah_pecahan?: array<string, int|string|null>|null}>  $rincian
     */
    public function simpan(User $user, BukuKas $bukuKas, mixed $tanggal, ?string $catatan, array $rincian): AuditSaldoDompet
    {
        if ($bukuKas->user_id !== $user->id || ! $user->dapatMengelolaTransaksiPada($bukuKas)) {
            throw new AuthorizationException('Kas audit tidak dapat dikelola.');
        }

        if ($rincian === []) {
            throw ValidationException::withMessages(['rincian' => 'Minimal satu dompet harus diaudit.']);
        }

        $proses = function () use ($user, $bukuKas, $tanggal, $catatan, $rincian): AuditSaldoDompet {
            $idSumberDana = collect($rincian)->pluck('dompet_id')->map(fn ($id): int => (int) $id);

            if ($idSumberDana->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['rincian' => 'Sumber dana audit tidak boleh duplikat.']);
            }

            $sumberDana = SumberDana::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereKey($idSumberDana)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($sumberDana->count() !== $idSumberDana->count()) {
                throw ValidationException::withMessages(['rincian' => 'Salah satu sumber dana sudah tidak tersedia.']);
            }

            BukuKas::withoutGlobalScopes()->whereKey($bukuKas->id)->lockForUpdate()->firstOrFail();

            $totalAplikasi = 0;
            $totalRiil = 0;

            foreach ($rincian as $index => $item) {
                $record = $sumberDana->get((int) $item['dompet_id']);

                if ($record->user_id !== $user->id || ! $user->dapatMengelolaTransaksiPadaDompet($record)) {
                    throw new AuthorizationException('Sumber dana audit tidak dapat dikelola.');
                }

                if (! Transaksi::withoutGlobalScopes()
                    ->where('buku_kas_id', $bukuKas->id)
                    ->where('sumber_dana_id', $record->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'rincian' => "Sumber dana {$record->nama_dompet} tidak memiliki transaksi pada kas terpilih.",
                    ]);
                }

                if ((int) $record->saldo !== (int) $item['saldo_aplikasi']) {
                    throw ValidationException::withMessages([
                        'rincian' => "Saldo sumber dana {$record->nama_dompet} telah berubah. Muat ulang data audit.",
                    ]);
                }

                if (filled($item['jumlah_pecahan'] ?? null) && ! $record->mendukungHitungUang()) {
                    throw ValidationException::withMessages([
                        'rincian' => "Pecahan uang hanya valid untuk sumber dana berjenis Tunai.",
                    ]);
                }

                $totalPecahan = $record->mendukungHitungUang() ? $this->totalUangDihitung($item['jumlah_pecahan'] ?? []) : null;
                $saldoRiil = $totalPecahan ?? (int) $item['saldo_riil'];

                $rincian[$index]['saldo_riil'] = $saldoRiil;
                unset($rincian[$index]['jumlah_pecahan']);

                $totalAplikasi += (int) $record->saldo;
                $totalRiil += $saldoRiil;
            }

            $audit = AuditSaldoDompet::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'buku_kas_id' => $bukuKas->id,
                'tanggal' => $tanggal ?? now(),
                'catatan' => filled($catatan) ? trim($catatan) : null,
                'total_saldo_aplikasi' => $totalAplikasi,
                'total_saldo_riil' => $totalRiil,
                'total_selisih' => $totalRiil - $totalAplikasi,
            ]);

            foreach ($rincian as $item) {
                $record = $sumberDana->get((int) $item['dompet_id']);
                $saldoRiilAkhir = (int) $item['saldo_riil'];
                $selisih = $saldoRiilAkhir - (int) $record->saldo;

                $detail = AuditSaldoDompetDetail::create([
                    'audit_saldo_dompet_id' => $audit->id,
                    'sumber_dana_id' => $record->id,
                    'nama_sumber_dana' => $record->nama_dompet,
                    'saldo_aplikasi' => (int) $record->saldo,
                    'saldo_riil' => $saldoRiilAkhir,
                    'selisih' => $saldoRiilAkhir - (int) $record->saldo,
                    'catatan' => filled($item['catatan'] ?? null) ? trim($item['catatan']) : null,
                ]);

                $selisihAkhir = $saldoRiilAkhir - (int) $record->saldo;

                if ($selisihAkhir === 0) {
                    continue;
                }

                $jenis = $selisihAkhir > 0 ? 'Pemasukan' : 'Pengeluaran';
                $transaksi = app(TransaksiService::class)->buat($user, [
                    'buku_kas_id' => $bukuKas->id,
                    'sumber_dana_id' => $record->id,
                    'tanggal' => $tanggal ?? now(),
                    'nominal' => abs($selisihAkhir),
                    'deskripsi' => filled($catatan)
                        ? 'Penyesuaian saldo dompet: '.trim($catatan)
                        : 'Penyesuaian saldo dompet',
                ], $jenis);

                Transaksi::withoutEvents(fn () => $transaksi->update([
                    'audit_saldo_dompet_detail_id' => $detail->id,
                ]));
            }

            return $audit->load('detail.transaksi');
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }

    private function totalUangDihitung(mixed $pecahan): ?int
    {
        if (! is_array($pecahan)) {
            throw ValidationException::withMessages([
                'rincian' => 'Data hitung uang tidak valid.',
            ]);
        }

        $nominalDiisi = false;
        $total = 0;

        foreach (self::PECAHAN_UANG as $daftarNominal) {
            foreach ($daftarNominal as $kunci => $nominal) {
                $jumlah = $pecahan[$kunci] ?? null;

                if ($jumlah === null || $jumlah === '') {
                    continue;
                }

                $nominalDiisi = true;

                if ((! is_int($jumlah) && ! is_string($jumlah)) || ! preg_match('/^\d+$/D', (string) $jumlah)) {
                    throw ValidationException::withMessages([
                        'rincian' => 'Jumlah lembar atau keping harus berupa bilangan bulat nonnegatif.',
                    ]);
                }

                $jumlah = (int) $jumlah;

                if ($jumlah > 999999 || $jumlah > intdiv(PHP_INT_MAX - $total, $nominal)) {
                    throw ValidationException::withMessages([
                        'rincian' => 'Jumlah lembar atau keping melebihi batas yang dapat dihitung.',
                    ]);
                }

                $total += $nominal * $jumlah;
            }
        }

        return $nominalDiisi ? $total : null;
    }
}
