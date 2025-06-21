<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('X-Livewire')) {
            Log::channel('daily')->info('LIVEWIRE REQUEST', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'component' => $request->input('components.0.fingerprint.name') ?? 'unknown',
                'method_called' => $request->input('components.0.calls.0.method') ?? 'none',
                'path' => $request->input('components.0.calls.0.path') ?? 'none',
                'session_id' => session()->getId(),
                'csrf_token' => $request->input('_token'),
                'session_token' => session()->token(),
                'tokens_match' => $request->input('_token') === session()->token(),
                'user_id' => auth()->id(),
                'headers' => $request->headers->all(),
            ]);
        }

        $response = $next($request);

        if ($request->hasHeader('X-Livewire') && method_exists($response, 'isRedirect') && $response->isRedirect()) {
            $location = 'unknown';
            if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
                $location = $response->headers->get('Location');
            }
            
            Log::channel('daily')->error('LIVEWIRE REDIRECT DETECTED', [
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 'unknown',
                'location' => $location,
                'content' => method_exists($response, 'getContent') ? substr($response->getContent(), 0, 1000) : 'unknown',
            ]);
        }

        return $response;
    }
}