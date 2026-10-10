<?php

namespace App\Console\Commands;

use App\Enums\StatusTrialPremium;
use App\Models\TrialPremium;
use App\Notifications\TrialPremiumBerakhir;
use App\Notifications\TrialPremiumPengingat;
use Illuminate\Console\Command;

// Perintah harian trial Premium: pengingat H-7/H-1 dan penutupan saat berakhir.
class ProsesTrialPremium extends Command
{
    protected $signature = 'trial-premium:proses';

    protected $description = 'Mengirim pengingat trial Premium dan menutup trial yang sudah berakhir';

    public function handle(): int
    {
        $hariIni = today();
        $pengingatTerkirim = 0;
        $ditutup = 0;

        // Pengingat H-7 dan H-1 untuk trial aktif.
        foreach ([7, 1] as $sisaHari) {
            $tanggal = $hariIni->copy()->addDays($sisaHari)->toDateString();

            TrialPremium::query()
                ->where('status', StatusTrialPremium::Aktif)
                ->whereDate('berakhir_pada', $tanggal)
                ->with('user')
                ->chunkById(100, function ($trials) use ($sisaHari, &$pengingatTerkirim): void {
                    foreach ($trials as $trial) {
                        if ($trial->user === null) {
                            continue;
                        }

                        $pengingat = new TrialPremiumPengingat($trial, $sisaHari);
                        $sudahDikirim = $trial->user->notifications()
                            ->where('data->reminder_key', $pengingat->reminderKey())
                            ->exists();

                        if ($sudahDikirim) {
                            continue;
                        }

                        $trial->user->notify($pengingat);
                        $pengingatTerkirim++;
                    }
                });
        }

        // Penutupan trial yang lewat tanggal berakhir.
        TrialPremium::query()
            ->where('status', StatusTrialPremium::Aktif)
            ->whereDate('berakhir_pada', '<', $hariIni->toDateString())
            ->with(['user'])
            ->chunkById(100, function ($trials) use ($hariIni, &$ditutup): void {
                foreach ($trials as $trial) {
                    $trial->update(['status' => StatusTrialPremium::Berakhir]);

                    $user = $trial->user;

                    if ($user === null) {
                        $ditutup++;

                        continue;
                    }

                    // Kembali menjadi Reguler bila masa aktif tidak diperpanjang berbayar.
                    // Sisa trial yang dikonversi sudah ditambah ke masa aktif berbayar saat persetujuan,
                    // sehingga pengguna tersebut tidak lagi memakai tanggal trial.
                    if ($user->masa_aktif === null || $user->masa_aktif->toDateString() <= $trial->berakhir_pada->toDateString()) {
                        $user->update(['type' => 'reguler']);
                    }

                    $terkunci = $user->buku_kas()->count() + $user->sumberDana()->count();
                    $terkunci = max(0, $terkunci - 4);

                    $user->notify(new TrialPremiumBerakhir($trial, $terkunci));
                    $ditutup++;
                }
            });

        $this->info("{$pengingatTerkirim} pengingat trial dan {$ditutup} penutupan trial berhasil diproses.");

        return Command::SUCCESS;
    }
}
