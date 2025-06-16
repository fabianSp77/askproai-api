<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugLivewire
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-Livewire')) {
            Log::info('Livewire request', [
                'url' => $request->fullUrl(),
                'component' => $request->input('components.0.snapshot'),
                'method' => $request->input('components.0.calls.0.method'),
                'params' => $request->input('components.0.calls.0.params'),
                'fingerprint' => $request->input('components.0.fingerprint'),
                'session_exists' => $request->hasSession(),
                'csrf_token_valid' => $request->session()->token() === $request->input('_token'),
            ]);
        }
        
        $response = $next($request);
        
        // Check if Livewire is redirecting
        if ($request->header('X-Livewire') && $response->status() === 302) {
            Log::warning('Livewire redirect detected', [
                'from' => $request->fullUrl(),
                'to' => $response->headers->get('Location'),
                'component' => $request->input('components.0.fingerprint.name'),
            ]);
        }
        
        return $response;
    }
}