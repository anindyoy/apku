<?php

namespace App\Http\Controllers;

use App\Models\Langganan;
use Illuminate\Support\Facades\Gate;

class LanggananInvoiceController extends Controller
{
    public function __invoke(Langganan $langganan)
    {
        // Hanya pemilik order atau admin yang boleh membuka invoice.
        Gate::authorize('view', $langganan);

        $langganan->loadMissing(['user', 'verifier']);

        return response()
            ->view('langganan-invoice', ['order' => $langganan])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
