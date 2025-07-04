<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogLivewireErrors
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('livewire/*')) {
            Log::channel('single')->info('Livewire Request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'has_session' => $request->hasSession(),
                'session_id' => $request->session()->getId() ?? 'none',
            ]);
        }
        
        try {
            $response = $next($request);
            
            if ($request->is('livewire/*') && $response->getStatusCode() >= 400) {
                Log::channel('single')->error('Livewire Error Response', [
                    'status' => $response->getStatusCode(),
                    'content' => substr($response->getContent(), 0, 1000),
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            if ($request->is('livewire/*')) {
                Log::channel('single')->error('Livewire Exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => array_slice($e->getTrace(), 0, 5),
                ]);
            }
            throw $e;
        }
    }
}