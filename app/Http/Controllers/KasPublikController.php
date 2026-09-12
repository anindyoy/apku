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
        $data = $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $data['bulan'] ?? now()->format('Y-m');
        $mulai = CarbonImmutable::createFromFormat('!Y-m', $bulan);
        $akhir = $mulai->addMonth();

        $query = DB::table('transaksi')->where('transaksi.buku_kas_id', $kas->id)
            ->where('tanggal', '>=', $mulai)->where('tanggal', '<', $akhir);
        $pemasukan = (clone $query)->whereIn('jenis', ['Pemasukan', 'Transfer Pemasukan'])->sum('nominal');
        $pengeluaran = (clone $query)->whereIn('jenis', ['Pengeluaran', 'Transfer Pengeluaran'])->sum('nominal');
        $transaksi = $query->leftJoin('jenis_transaksi', 'jenis_transaksi.id', '=', 'transaksi.jenis_transaksi_id')
            ->select(['transaksi.tanggal', 'transaksi.jenis', 'transaksi.nominal', 'transaksi.deskripsi', 'jenis_transaksi.nama_jenis as aktivitas'])
            ->orderByDesc('transaksi.tanggal')->orderByDesc('transaksi.id')
            ->paginate(25)->appends(['bulan' => $bulan]);

        return response()->view('kas-publik', compact('kas', 'bulan', 'pemasukan', 'pengeluaran', 'transaksi', 'share'))
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
