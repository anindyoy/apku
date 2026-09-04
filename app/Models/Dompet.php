<?php

namespace App\Models;

use App\Models\Concerns\MembersihkanCacheOpsiSelect;
use App\Models\Scopes\UserScope;
use Database\Factories\DompetFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([UserScope::class])]
class Dompet extends Model
{
    /** @use HasFactory<DompetFactory> */
    use HasFactory, MembersihkanCacheOpsiSelect, SoftDeletes;

    protected $table = 'dompet';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    protected function cacheOpsiSelectEntitas(): string
    {
        return 'dompet';
    }
}
