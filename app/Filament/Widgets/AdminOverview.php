<?php

namespace App\Filament\Widgets;

use App\Enums\StatusLangganan;
use App\Filament\Resources\LanggananResource;
use App\Filament\Resources\UserResource;
use App\Models\Langganan;
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
        $data = DashboardCache::remember('admin', auth()->id(), fn () => [
            'users' => User::query()->notAdmin()->count(),
            'premium' => User::query()->notAdmin()->where('masa_aktif', '>=', today())->count(),
            'pending' => Langganan::query()->where('status', StatusLangganan::MenungguVerifikasi)->count(),
        ]);

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
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
