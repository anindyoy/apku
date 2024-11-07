<?php

namespace App\Models;

use App\Models\User;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy([UserScope::class])]
class BukuKas extends Model
{
    /** @use HasFactory<\Database\Factories\BukuKasFactory> */
    use HasFactory;
    protected $table = 'buku_kas';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getRandomBukuKas($user_id)
    {
        return self::where('user_id', $user_id)
            ->inRandomOrder();
    }
}
