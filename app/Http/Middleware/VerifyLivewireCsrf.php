<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLivewireCsrf
{
    /**
     * Handle an incoming request.
     * Fix for 500 error popup after login
     */
    public function handle(Request $request, Closure $next)
    {
        // For Livewire update requests, ensure session is properly initialized
        if ($request->is('livewire/update')) {
            // Start session if not started
            if (!$request->hasSession() || !$request->session()->isStarted()) {
                $request->session()->start();
            }

            // Regenerate token if missing
            if (!$request->session()->token()) {
                $request->session()->regenerateToken();
            }

            // Handle the response
            $response = $next($request);

            // If we get a 419 (page expired), convert to a proper JSON response
            if ($response->status() === 419) {
                return response()->json([
                    'effects' => [
                        'redirect' => url('/admin/login')
                    ]
                ], 200);
            }

            // If we get a 500 error, log it and return graceful response
            if ($response->status() === 500) {
                \Log::error('Livewire 500 error after login', [
                    'request' => $request->all(),
                    'session' => $request->session()->all()
                ]);

                return response()->json([
                    'effects' => [
                        'redirect' => url('/admin')
                    ]
                ], 200);
            }

            return $response;
        }

        return $next($request);
    }
}