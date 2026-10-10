<?php

namespace App\Console\Commands;

use App\Enums\StatusTrialPremium;
use App\Models\TrialPremium;
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

        // User dengan trial aktif memakai pengingat khusus trial (H-7/H-1), bukan pengingat bawaan.
        $idUserTrial = TrialPremium::query()
            ->where('status', StatusTrialPremium::Aktif)
            ->pluck('user_id')
            ->filter()
            ->all();

        foreach ([30, 7] as $jumlahHari) {
            $tanggalBerakhir = today()->addDays($jumlahHari);

            User::query()
                ->whereDate('masa_aktif', $tanggalBerakhir)
                ->where('role', '!=', 'admin')
                ->whereNotIn('id', $idUserTrial)
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

        // Pakai konstanta Command agar konsisten dengan perintah trial.
        return Command::SUCCESS;
    }
}
