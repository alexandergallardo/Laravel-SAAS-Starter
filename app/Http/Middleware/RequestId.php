<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    /**
     * Attach a request id to the request, response and log context.
     *
     * Honors an inbound X-Request-Id header when present, otherwise generates
     * an ordered UUID. The id is pushed into the request Context so it is
     * appended to every log entry and propagated to any queued jobs.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::orderedUuid();

        $request->attributes->set('request_id', $requestId);

        Context::add('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
