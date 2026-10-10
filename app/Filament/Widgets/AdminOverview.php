<?php

namespace App\Filament\Widgets;

use App\Enums\StatusLangganan;
use App\Enums\StatusTrialPremium;
use App\Filament\Resources\LanggananResource;
use App\Filament\Resources\TrialPremiumResource;
use App\Filament\Resources\UserResource;
use App\Models\Langganan;
use App\Models\TrialPremium;
use App\Models\User;
use App\Services\DashboardCache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends BaseWidget
{
    protected ?string $heading = 'Ringkasan administrasi';

    protected ?string $description = 'Hanya menampilkan data agregat tanpa rincian keuangan pribadi pengguna.';

    protected function getStats(): array
    {
        // Statistik trial: aktif, berakhir minggu ini, total, konversi, dan persentase konversi.
        $data = DashboardCache::remember('admin', auth()->id(), function () {
            $totalTrial = TrialPremium::query()->count();
            $trialAktif = TrialPremium::query()->where('status', StatusTrialPremium::Aktif)->count();
            $trialBerakhirMingguIni = TrialPremium::query()
                ->where('status', StatusTrialPremium::Berakhir)
                ->whereDate('updated_at', '>=', today()->copy()->subDays(7))
                ->count();
            $konversi = TrialPremium::query()->where('status', StatusTrialPremium::Dikonversi)->count();

            return [
                'users' => User::query()->notAdmin()->count(),
                'premium' => User::query()->notAdmin()->where('masa_aktif', '>=', today())->count(),
                'pending' => Langganan::query()->where('status', StatusLangganan::MenungguVerifikasi)->count(),
                'trial_aktif' => $trialAktif,
                'trial_minggu_ini' => $trialBerakhirMingguIni,
                'trial_total' => $totalTrial,
                'trial_konversi' => $konversi,
                // Persentase konversi memakai pembulatan satu desimal agar stabil di widget.
                'trial_persen' => $totalTrial > 0 ? round($konversi / $totalTrial * 100, 1) : 0,
            ];
        });

        return [
            Stat::make('Pengguna', $data['users'])
                ->description('Kelola akun pengguna')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl('index')),
            Stat::make(
                'Premium aktif',
                $data['premium'],
            )
                ->description('Akun dengan masa aktif berlaku')
                ->icon('heroicon-o-sparkles'),
            Stat::make(
                'Menunggu verifikasi',
                $data['pending'],
            )
                ->description('Pembayaran yang perlu ditinjau')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(LanggananResource::getUrl('index')),
            Stat::make('Trial aktif', $data['trial_aktif'])
                ->description('Trial berakhir minggu ini: '.$data['trial_minggu_ini'])
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->url(TrialPremiumResource::getUrl('index')),
            Stat::make('Total trial', $data['trial_total'])
                ->description('Konversi: '.$data['trial_konversi'].' ('.$data['trial_persen'].'%)')
                ->icon('heroicon-o-chart-bar')
                ->url(TrialPremiumResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
