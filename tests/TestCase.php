<?php

namespace Tests;

use App\Filament\Pages\AkunSaya;
use App\Filament\Pages\Kategori;
use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\PiutangResource\Pages\ListPiutangs;
use App\Filament\Resources\TransaksiResource\Pages\ListTransaksis;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UtangResource\Pages\ListUtangs;
use App\Filament\Resources\UtangResource\Pages\UtangDetail;
use App\Models\User;
use App\Models\UtangPiutang;
use Filament\Auth\Pages\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Livewire;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders after migration
        if (app()->environment('testing')) {
            $this->seed();
        }
    }

    public function renderTest($page, $record = false)
    {
        $pageClass = [
            'akun_saya' => AkunSaya::class,
            'buku_kas' => ListBukuKas::class,
            'kategori' => Kategori::class,
            'transaksi' => ListTransaksis::class,
            'piutang' => ListPiutangs::class,
            'utang' => ListUtangs::class,
            'detail_piutang' => UtangDetail::class,
            'register' => Register::class,
            'user' => ListUsers::class,
            'edit_user' => EditUser::class,
        ];

        if ($page == 'register') {
            Livewire::test($pageClass[$page])->assertSuccessful();

            return; // Early return to simplify the structure
        }

        $user = User::inRandomOrder()
            ->when(
                in_array($page, ['user', 'edit_user']), // Halaman ini memerlukan admin.
                fn ($query) => $query->admin(),
                fn ($query) => $query->notAdmin()
            )
            ->first();

        $pageRecord = null;
        if ($record) {
            $pageRecord = match ($page) {
                'edit_user' => User::inRandomOrder()->first()->id,
                'detail_piutang' => UtangPiutang::whereUserId($user->id)
                    ->inRandomOrder()
                    ->first()->code,
                default => null,
            };
        }

        // dd($pageClass[$page], $record ? ['record' => $pageRecord] : []);
        Livewire::actingAs($user)
            ->test($pageClass[$page], $record ? ['record' => $pageRecord] : []) // Conditional parameters
            ->assertSuccessful();
    }
}
