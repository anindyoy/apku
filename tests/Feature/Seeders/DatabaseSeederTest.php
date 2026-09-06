<?php

use App\Enums\StatusLangganan;
use App\Models\Dompet;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UtangPiutang;
use App\Models\UtangPiutangDetail;
use App\Models\Voucher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

test('database seeder memberi gambaran untuk seluruh fitur utama', function () {
    app(DatabaseSeeder::class)->run();

    $user = User::where('email', 'reguler@apku.com')->firstOrFail();

    expect(User::admin()->exists())->toBeTrue()
        ->and($user->type)->toBe('reguler')
        ->and(User::where('email', 'premium@apku.com')->value('type'))->toBe('premium')
        ->and($user->buku_kas()->count())->toBeGreaterThanOrEqual(2)
        ->and(Dompet::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBeGreaterThanOrEqual(2)
        ->and(Transaksi::withoutGlobalScopes()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(Transaksi::withoutGlobalScopes()->get()->every(fn (Transaksi $item): bool => $item->nominal >= 1000 && $item->nominal <= 100000 && $item->nominal % 1000 === 0))->toBeTrue()
        ->and(UtangPiutang::withoutGlobalScopes()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(UtangPiutangDetail::withoutGlobalScopes()->get()->every(fn (UtangPiutangDetail $item): bool => $item->nominal >= 1000 && $item->nominal <= 100000 && $item->nominal % 1000 === 0))->toBeTrue()
        ->and(PaketLangganan::count())->toBeGreaterThanOrEqual(3)
        ->and(MetodePembayaran::count())->toBeGreaterThanOrEqual(3)
        ->and(Voucher::whereHas('codes')->count())->toBeGreaterThanOrEqual(2)
        ->and(Langganan::distinct()->count('status'))->toBe(count(StatusLangganan::cases()))
        ->and(DB::table('notifications')->exists())->toBeTrue();
});

test('database seeder dapat dijalankan ulang tanpa menggandakan data demo', function () {
    app(DatabaseSeeder::class)->run();
    app(DatabaseSeeder::class)->run();

    expect(User::where('email', 'admin@apku.com')->count())->toBe(1)
        ->and(PaketLangganan::count())->toBe(3)
        ->and(MetodePembayaran::count())->toBe(3)
        ->and(Voucher::count())->toBe(2)
        ->and(Langganan::count())->toBe(count(StatusLangganan::cases()));
});
