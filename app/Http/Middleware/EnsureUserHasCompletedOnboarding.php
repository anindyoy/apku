<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Onboarding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompletedOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuper() && ! $user->buku_kas()->exists() && ! $request->routeIs('filament.admin.pages.onboarding')) {
            return redirect(Onboarding::getUrl());
        }

        return $next($request);
    }
}
