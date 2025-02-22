<?php

namespace App\Filament\Pages;

use App\Models\JenisTransaksi;
use Filament\Pages\Page;
use App\Models\Transaksi;

class CekQuery extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.cek-query';

    public function mount()
    {
        dd($this->createTransaksiLama());
    }

    private function createTransaksiLama()
    {
        $user_id = 2;
        $kas_id = 1;
        $tanggal = 13;
        $tanggal_awal = date('Y-m-' . $tanggal);
        $tanggal_akhir = date('Y-m-' . $tanggal + 1);

        $transaksi_ke_3_terakhir = Transaksi::where('user_id', $user_id)
            ->where('buku_kas_id', $kas_id)
            ->orderby('tanggal', 'desc')
            ->skip(2)
            ->first()
            ->tanggal;

        // dd($transaksi_ke_3_terakhir);

        // $transaksi_terakhir = Transaksi::where('user_id', $user_id)
        //     ->where('buku_kas_id', $kas_id)
        //     ->where('tanggal', '>=', $tanggal_awal)
        //     ->where('tanggal', '<=', $tanggal_akhir)
        //     ->orderby('tanggal', 'desc')
        //     ->first()
        //     ->tanggal;

        $new_tanggal = date('Y-m-d H:i:s', strtotime($transaksi_ke_3_terakhir . ' +1 minute'));
        $jenis = "Pemasukan";

        Transaksi::create([
            'user_id' => $user_id,
            'buku_kas_id' => $kas_id,
            'tanggal' => $new_tanggal,
            'nominal' => 10,
            'jenis_transaksi_id' => JenisTransaksi::whereTipe($jenis)
                ->whereUserId($user_id)
                ->inRandomOrder()
                ->first()
                ->id,
            'jenis' => $jenis,
        ]);

        dd('done');
    }
}
