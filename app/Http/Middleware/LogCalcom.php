<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogCalcom
{
    public function handle(Request $request, Closure $next)
    {
        /* → Route-Name statt Pfad benutzen */
        $isCalcom = $request->routeIs('calcom.bookings.*');

        if ($isCalcom) {
            Log::channel('calcom')->info('[OUT]', [
                'method' => $request->method(),
                'uri' => $request->path(),
                'payload' => $request->all(),
            ]);
        }

        $response = $next($request);

        if ($isCalcom) {
            Log::channel('calcom')->info('[IN ]', [
                'status' => $response->status(),
                'body' => $response->getContent(),
            ]);
        }

        return $response;
    }
}
