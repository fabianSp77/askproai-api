<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugLogin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin/login') || $request->is('livewire/*')) {
            Log::info('Login Request Debug', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_livewire' => $request->hasHeader('X-Livewire'),
                'has_csrf' => $request->has('_token'),
                'csrf_token' => $request->input('_token'),
                'session_token' => session()->token(),
                'auth_check' => auth()->check(),
                'user_id' => auth()->id(),
                'input' => $request->except(['password', '_token']),
                'headers' => [
                    'X-Livewire' => $request->header('X-Livewire'),
                    'X-Livewire-Validate' => $request->header('X-Livewire-Validate'),
                    'Content-Type' => $request->header('Content-Type'),
                ],
            ]);
        }
        
        $response = $next($request);
        
        if ($request->is('admin/login') && $response->isRedirect()) {
            Log::warning('Login Redirect Detected', [
                'location' => $response->headers->get('Location'),
                'status' => $response->getStatusCode(),
                'auth_after' => auth()->check(),
                'user_after' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}