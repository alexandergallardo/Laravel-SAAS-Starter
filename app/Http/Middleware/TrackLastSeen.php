<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * Update the authenticated user's last_seen_at timestamp at most once every 5 minutes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();
            $threshold = Carbon::now()->subMinutes(5);

            if ($user->last_seen_at === null || $user->last_seen_at->lt($threshold)) {
                $user->timestamps = false;
                $user->forceFill(['last_seen_at' => now()])->save();
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
