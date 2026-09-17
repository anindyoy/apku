<?php

namespace App\Http\Controllers;

use App\Models\ShareBuku;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasPublikController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $share = ShareBuku::query()->where('public_token', $token)
            ->whereNull('user_id')->where('privilege', 'viewer')->aktif()->firstOrFail();
        $kas = $share->buku_kas()->withoutGlobalScopes()->firstOrFail();
        $data = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);
        $bulan = $data['bulan'] ?? now()->format('Y-m');
        $pencarian = trim($data['q'] ?? '');
        $mulai = CarbonImmutable::createFromFormat('!Y-m', $bulan);
        $akhir = $mulai->addMonth();

        $query = DB::table('transaksi')->where('transaksi.buku_kas_id', $kas->id);
        $bulanan = (clone $query)->where('tanggal', '>=', $mulai)->where('tanggal', '<', $akhir);
        $pemasukan = (clone $bulanan)->whereIn('jenis', ['Pemasukan', 'Transfer Pemasukan'])->sum('nominal');
        $pengeluaran = (clone $bulanan)->whereIn('jenis', ['Pengeluaran', 'Transfer Pengeluaran'])->sum('nominal');
        $transaksi = $query->when($pencarian === '', fn ($query) => $query
            ->where('tanggal', '>=', $mulai)->where('tanggal', '<', $akhir))
            ->leftJoin('jenis_transaksi', 'jenis_transaksi.id', '=', 'transaksi.jenis_transaksi_id')
            ->when($pencarian !== '', function ($query) use ($pencarian): void {
                $query->where(function ($query) use ($pencarian): void {
                    $query->where('transaksi.deskripsi', 'like', '%'.$pencarian.'%')
                        ->orWhere('jenis_transaksi.nama_jenis', 'like', '%'.$pencarian.'%')
                        ->orWhere('transaksi.jenis', 'like', '%'.$pencarian.'%');
                });
            })
            ->select(['transaksi.tanggal', 'transaksi.jenis', 'transaksi.tipe_transfer', 'transaksi.nominal', 'transaksi.deskripsi', 'jenis_transaksi.nama_jenis as aktivitas'])
            ->orderByDesc('transaksi.tanggal')->orderByDesc('transaksi.id')
            ->paginate(25)->appends(['bulan' => $bulan, 'q' => $pencarian]);

        return response()->view('kas-publik', compact('kas', 'bulan', 'pencarian', 'pemasukan', 'pengeluaran', 'transaksi', 'share'))
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
