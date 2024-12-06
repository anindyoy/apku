<?php

namespace App\Observers;

use App\Models\UtangPiutang;

class UtangPiutangObserver
{
    /**
     * Handle the UtangPiutang "created" event.
     */
    public function created(UtangPiutang $utangPiutang): void
    {
        //
    }

    /**
     * Handle the UtangPiutang "updated" event.
     */
    public function updated(UtangPiutang $utangPiutang): void
    {
        //
    }

    /**
     * Handle the UtangPiutang "deleted" event.
     */
    public function deleted(UtangPiutang $utangPiutang): void
    {
        $utangPiutang->utang_piutang_detail()->delete();
    }

    /**
     * Handle the UtangPiutang "restored" event.
     */
    public function restored(UtangPiutang $utangPiutang): void
    {
        //
    }

    /**
     * Handle the UtangPiutang "force deleted" event.
     */
    public function forceDeleted(UtangPiutang $utangPiutang): void
    {
        //
    }
}
