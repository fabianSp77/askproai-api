<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Only debug Livewire requests
        if ($request->path() !== 'livewire/update') {
            return $next($request);
        }

        try {
            Log::info('Livewire Request Debug', [
                'method' => $request->method(),
                'path' => $request->path(),
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'session_id' => session()->getId(),
                'csrf_token' => $request->session()->token(),
            ]);

            $response = $next($request);

            Log::info('Livewire Response Debug', [
                'status' => $response->getStatusCode(),
                'content' => substr($response->getContent(), 0, 500),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Livewire Request Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            throw $e;
        }
    }
}
