<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $feature  Feature path in dot notation (e.g., 'workspace.billing')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (FeatureService::isDisabled($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This feature is currently disabled.',
                    'feature' => $feature,
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'This feature is currently disabled.');
        }

        return $next($request);
    }
}
