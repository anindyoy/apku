<?php

namespace App\Services;

use App\Models\AuditSaldoDompet;
use App\Models\AuditSaldoDompetDetail;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\JenisTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditSaldoDompetService
{
    /**
     * @param  array<int, array{dompet_id: int, saldo_aplikasi: int, saldo_riil: int, catatan?: string|null}>  $rincian
     */
    public function simpan(User $user, BukuKas $bukuKas, mixed $tanggal, string $catatan, array $rincian): AuditSaldoDompet
    {
        if (trim($catatan) === '') {
            throw ValidationException::withMessages(['catatan' => 'Catatan audit wajib diisi.']);
        }

        if ($bukuKas->user_id !== $user->id || ! $user->dapatMengelolaTransaksiPada($bukuKas)) {
            throw new AuthorizationException('Kas audit tidak dapat dikelola.');
        }

        if ($rincian === []) {
            throw ValidationException::withMessages(['rincian' => 'Minimal satu dompet harus diaudit.']);
        }

        $proses = function () use ($user, $bukuKas, $tanggal, $catatan, $rincian): AuditSaldoDompet {
            $idDompet = collect($rincian)->pluck('dompet_id')->map(fn ($id): int => (int) $id);

            if ($idDompet->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['rincian' => 'Dompet audit tidak boleh duplikat.']);
            }

            $dompet = Dompet::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereKey($idDompet)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($dompet->count() !== $idDompet->count()) {
                throw ValidationException::withMessages(['rincian' => 'Salah satu dompet sudah tidak tersedia.']);
            }

            BukuKas::withoutGlobalScopes()->whereKey($bukuKas->id)->lockForUpdate()->firstOrFail();

            $totalAplikasi = 0;
            $totalRiil = 0;

            foreach ($rincian as $item) {
                $record = $dompet->get((int) $item['dompet_id']);

                if ($record->user_id !== $user->id || ! $user->dapatMengelolaTransaksiPadaDompet($record)) {
                    throw new AuthorizationException('Dompet audit tidak dapat dikelola.');
                }

                if ((int) $record->saldo !== (int) $item['saldo_aplikasi']) {
                    throw ValidationException::withMessages([
                        'rincian' => "Saldo dompet {$record->nama_dompet} telah berubah. Muat ulang data audit.",
                    ]);
                }

                $totalAplikasi += (int) $record->saldo;
                $totalRiil += (int) $item['saldo_riil'];
            }

            $audit = AuditSaldoDompet::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'buku_kas_id' => $bukuKas->id,
                'tanggal' => $tanggal ?? now(),
                'catatan' => trim($catatan),
                'total_saldo_aplikasi' => $totalAplikasi,
                'total_saldo_riil' => $totalRiil,
                'total_selisih' => $totalRiil - $totalAplikasi,
            ]);

            foreach ($rincian as $item) {
                $record = $dompet->get((int) $item['dompet_id']);
                $selisih = (int) $item['saldo_riil'] - (int) $record->saldo;
                $detail = AuditSaldoDompetDetail::create([
                    'audit_saldo_dompet_id' => $audit->id,
                    'dompet_id' => $record->id,
                    'nama_dompet' => $record->nama_dompet,
                    'saldo_aplikasi' => (int) $record->saldo,
                    'saldo_riil' => (int) $item['saldo_riil'],
                    'selisih' => $selisih,
                    'catatan' => filled($item['catatan'] ?? null) ? trim($item['catatan']) : null,
                ]);

                if ($selisih === 0) {
                    continue;
                }

                $jenis = $selisih > 0 ? 'Pemasukan' : 'Pengeluaran';
                $kategori = JenisTransaksi::withoutGlobalScopes()->firstOrCreate([
                    'user_id' => $user->id,
                    'nama_jenis' => 'Audit Saldo',
                    'tipe' => $jenis,
                ], ['is_system' => true]);

                if (! $kategori->is_system) {
                    $kategori->update(['is_system' => true]);
                }

                $transaksi = app(TransaksiService::class)->buat($user, [
                    'buku_kas_id' => $bukuKas->id,
                    'dompet_id' => $record->id,
                    'jenis_transaksi_id' => $kategori->id,
                    'tanggal' => $tanggal ?? now(),
                    'nominal' => abs($selisih),
                    'deskripsi' => 'Penyesuaian saldo dompet: '.trim($catatan),
                ], $jenis);

                Transaksi::withoutEvents(fn () => $transaksi->update([
                    'audit_saldo_dompet_detail_id' => $detail->id,
                ]));
            }

            return $audit->load('detail.transaksi');
        };

        return DB::transactionLevel() > 0 ? $proses() : DB::transaction($proses);
    }
}
