<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PengingatPerpanjanganMasaAktif;
use Illuminate\Console\Command;

class KirimPengingatMasaAktif extends Command
{
    protected $signature = 'masa-aktif:kirim-pengingat';

    protected $description = 'Mengirim notifikasi pengingat perpanjangan masa aktif H-30 dan H-7';

    public function handle(): int
    {
        $jumlahTerkirim = 0;

        foreach ([30, 7] as $jumlahHari) {
            $tanggalBerakhir = today()->addDays($jumlahHari);

            User::query()
                ->whereDate('masa_aktif', $tanggalBerakhir)
                ->where('role', '!=', 'admin')
                ->chunkById(100, function ($users) use ($jumlahHari, $tanggalBerakhir, &$jumlahTerkirim): void {
                    foreach ($users as $user) {
                        $pengingat = new PengingatPerpanjanganMasaAktif(
                            $jumlahHari,
                            $tanggalBerakhir->toDateString(),
                        );

                        $sudahDikirim = $user->notifications()
                            ->where('data->reminder_key', $pengingat->reminderKey())
                            ->exists();

                        if ($sudahDikirim) {
                            continue;
                        }

                        $user->notify($pengingat);
                        $jumlahTerkirim++;
                    }
                });
        }

        $this->info("{$jumlahTerkirim} pengingat masa aktif berhasil dikirim.");

        return self::SUCCESS;
    }
}
