<?php

namespace App\Models\Scopes;

use App\Models\BukuKas;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->id() && ! auth()->user()->isAdmin()) {
            if ($model instanceof BukuKas) {
                $builder->where(function (Builder $query): void {
                    $query->where('buku_kas.user_id', auth()->id())
                        ->orWhereExists(function ($query): void {
                            $query->selectRaw('1')
                                ->from('share_buku')
                                ->whereColumn('share_buku.buku_kas_id', 'buku_kas.id')
                                ->where('share_buku.user_id', auth()->id())
                                ->where(fn ($query) => $query->whereNull('berlaku_mulai')->orWhere('berlaku_mulai', '<=', now()))
                                ->where(fn ($query) => $query->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>', now()));
                        });

                });

                return;
            }

            if ($model instanceof Transaksi) {
                $builder->where(function (Builder $query): void {
                    $query->where('transaksi.user_id', auth()->id())
                        ->orWhereExists(function ($query): void {
                            $query->selectRaw('1')
                                ->from('buku_kas')
                                ->whereColumn('buku_kas.id', 'transaksi.buku_kas_id')
                                ->where(function ($query): void {
                                    $query->where('buku_kas.user_id', auth()->id())
                                        ->orWhereExists(function ($query): void {
                                            $query->selectRaw('1')
                                                ->from('share_buku')
                                                ->whereColumn('share_buku.buku_kas_id', 'buku_kas.id')
                                                ->where('share_buku.user_id', auth()->id())
                                                ->where(fn ($query) => $query->whereNull('berlaku_mulai')->orWhere('berlaku_mulai', '<=', now()))
                                                ->where(fn ($query) => $query->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>', now()));
                                        });
                                });
                        });
                });

                return;
            }

            $builder->where($model->qualifyColumn('user_id'), auth()->id());
        }
    }
}
