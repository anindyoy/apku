<?php

namespace App\Services;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class BatalkanLangganan
{
    public function handle(User $user, Langganan $langganan): Langganan
    {
        if ($langganan->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        if ($langganan->status !== StatusLangganan::MenungguPembayaran) {
            throw ValidationException::withMessages(['status' => 'Order ini tidak dapat dibatalkan.']);
        }

        $langganan->update(['status' => StatusLangganan::Dibatalkan]);

        return $langganan->refresh();
    }
}
