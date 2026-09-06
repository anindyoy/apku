<?php

namespace App\Services;

use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PastikanAkunKeuanganDefault
{
    /** @return array{bukuKas: BukuKas, dompet: Dompet} */
    public function jalankan(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $bukuKas = BukuKas::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            $dompet = Dompet::withoutGlobalScopes()
                ->withTrashed()
                ->where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if (! $bukuKas) {
                $bukuKas = BukuKas::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'nama_buku' => 'Kas Utama',
                    'saldo' => (int) Dompet::withoutGlobalScopes()->where('user_id', $user->id)->sum('saldo'),
                    'is_default' => true,
                    'description' => 'Kas utama',
                ]);
            }

            if (! $dompet) {
                $dompet = Dompet::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'nama_dompet' => 'Cash',
                    'saldo' => (int) BukuKas::withoutGlobalScopes()->where('user_id', $user->id)->sum('saldo'),
                    'is_default' => true,
                    'description' => 'Dompet tunai utama',
                ]);
            } elseif ($dompet->trashed()) {
                $dompet->restore();
            }

            BukuKas::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereKeyNot($bukuKas->id)
                ->update(['is_default' => false]);
            Dompet::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereKeyNot($dompet->id)
                ->update(['is_default' => false]);

            if (! $bukuKas->is_default) {
                $bukuKas->update(['is_default' => true]);
            }

            if (! $dompet->is_default) {
                $dompet->update(['is_default' => true]);
            }

            return ['bukuKas' => $bukuKas->fresh(), 'dompet' => $dompet->fresh()];
        });
    }
}
