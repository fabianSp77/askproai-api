<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixLoginError
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);

            // If we get a 500 error after login, catch it
            if ($response->status() === 500 && $request->is('livewire/update')) {
                \Log::error('500 Error in Livewire update', [
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'user' => auth()->check() ? auth()->user()->email : 'guest'
                ]);

                // Return a redirect response instead
                if (auth()->check()) {
                    return response()->json([
                        'effects' => [
                            'redirect' => '/admin'
                        ]
                    ], 200);
                }
            }

            return $response;
        } catch (\Exception $e) {
            \Log::error('Middleware caught exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // If login was successful but navigation fails, redirect to dashboard
            if (auth()->check() && $request->is('livewire/update')) {
                return response()->json([
                    'effects' => [
                        'redirect' => '/admin'
                    ]
                ], 200);
            }

            throw $e;
        }
    }
}