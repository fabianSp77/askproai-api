<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireErrorHandler
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            
            // Log 500 errors for Livewire requests
            if ($request->hasHeader('X-Livewire') && method_exists($response, 'status') && $response->status() >= 500) {
                Log::error('Livewire 500 Error Response', [
                    'status' => $response->status(),
                    'content' => substr($response->getContent(), 0, 1000),
                    'request_path' => $request->path(),
                    'request_data' => $request->all(),
                ]);
            }
            
            return $response;
        } catch (\Throwable $e) {
            Log::error('Livewire Request Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5),
                'request_path' => $request->path(),
                'request_data' => $request->all(),
                'is_livewire' => $request->hasHeader('X-Livewire'),
            ]);
            
            throw $e;
        }
    }
}