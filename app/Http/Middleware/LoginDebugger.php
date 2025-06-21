<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginDebugger
{
    public function handle(Request $request, Closure $next)
    {
        // Temporarily disable this middleware to fix the error
        return $next($request);
        
        /* Disabled due to Livewire Redirector error
        // Log all login attempts
        if ($request->is('*/login') || $request->is('livewire/update')) {
            $logFile = storage_path('logs/login-debug-' . date('Y-m-d') . '.log');
            
            $logData = [
                'timestamp' => now()->toISOString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_ajax' => $request->ajax(),
                'is_livewire' => $request->hasHeader('X-Livewire'),
                'session_id' => session()->getId(),
                'csrf_token' => $request->input('_token', 'none'),
                'csrf_header' => $request->header('X-CSRF-TOKEN', 'none'),
                'auth_before' => auth()->check(),
                'user_before' => auth()->user()?->email,
                'session_driver' => config('session.driver'),
                'session_lifetime' => config('session.lifetime'),
                'session_domain' => config('session.domain'),
                'session_path' => config('session.path'),
                'cookies' => array_keys($request->cookies->all()),
                'headers' => [
                    'User-Agent' => $request->header('User-Agent'),
                    'Accept' => $request->header('Accept'),
                    'X-Livewire' => $request->header('X-Livewire'),
                ],
            ];
            
            // Log request data for POST
            if ($request->isMethod('POST')) {
                $logData['post_data'] = $request->except(['password', 'password_confirmation']);
                if ($request->has('email')) {
                    $logData['login_email'] = $request->input('email');
                }
            }
            
            file_put_contents($logFile, "\n=== LOGIN REQUEST START ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        }
        
        $response = $next($request);
        
        // Log response
        if ($request->is('*/login') || $request->is('livewire/update')) {
            $logFile = storage_path('logs/login-debug-' . date('Y-m-d') . '.log');
            
            $responseData = [
                'timestamp' => now()->toISOString(),
                'auth_after' => auth()->check(),
                'user_after' => auth()->user()?->email,
                'session_id_after' => session()->getId(),
            ];
            
            // Handle different response types
            if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\JsonResponse) {
                $responseData['status'] = $response->getStatusCode();
                $responseData['is_redirect'] = false;
                
                // Check for set cookies
                if (property_exists($response, 'headers') && $response->headers) {
                    $responseData['cookies_set'] = [];
                    foreach ($response->headers->getCookies() as $cookie) {
                        $responseData['cookies_set'][] = [
                            'name' => $cookie->getName(),
                            'domain' => $cookie->getDomain(),
                            'path' => $cookie->getPath(),
                            'secure' => $cookie->isSecure(),
                            'httpOnly' => $cookie->isHttpOnly(),
                            'sameSite' => $cookie->getSameSite(),
                        ];
                    }
                }
            } elseif ($response instanceof \Illuminate\Http\RedirectResponse) {
                $responseData['status'] = $response->getStatusCode();
                $responseData['is_redirect'] = true;
                $responseData['redirect_url'] = $response->getTargetUrl();
            } else {
                // Handle Livewire redirects or other response types
                $responseData['response_type'] = get_class($response);
                $responseData['is_livewire_redirect'] = $response instanceof \Livewire\Features\SupportRedirects\Redirector;
            }
            
            file_put_contents($logFile, "=== LOGIN RESPONSE ===\n" . json_encode($responseData, JSON_PRETTY_PRINT) . "\n=== LOGIN REQUEST END ===\n\n", FILE_APPEND);
        }
        
        return $response;
        */
    }
}