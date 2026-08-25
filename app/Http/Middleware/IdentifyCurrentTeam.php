<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCurrentTeam
{
    /**
     * Ensure the authenticated user has a valid current team selected,
     * falling back to their first team membership if none is set.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->currentTeam) {
            $fallbackTeam = $user->teams()->oldest('team_user.id')->first();

            if ($fallbackTeam) {
                $user->switchTeam($fallbackTeam);
            }
        }

        return $next($request);
    }
}
