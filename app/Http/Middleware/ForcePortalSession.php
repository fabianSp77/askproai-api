<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PortalUser;

class ForcePortalSession
{
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if (!$request->is('business/*')) {
            return $next($request);
        }

        // Check if already authenticated
        if (Auth::guard('portal')->check()) {
            return $next($request);
        }

        // Try to restore session manually
        $sessionId = session()->getId();
        $sessionFile = storage_path('framework/sessions/portal/' . $sessionId);
        
        Log::debug('ForcePortalSession attempting restore', [
            'session_id' => $sessionId,
            'file_exists' => file_exists($sessionFile),
            'url' => $request->url(),
        ]);
        
        if (file_exists($sessionFile)) {
            try {
                $sessionData = unserialize(file_get_contents($sessionFile));
                
                // Look for Laravel's auth session key
                $authKey = 'login_portal_' . sha1(PortalUser::class);
                
                if (isset($sessionData[$authKey])) {
                    $userId = $sessionData[$authKey];
                    $user = PortalUser::find($userId);
                    
                    if ($user && $user->is_active) {
                        // Manually authenticate the user
                        Auth::guard('portal')->loginUsingId($userId);
                        
                        // Set company context
                        app()->instance('current_company_id', $user->company_id);
                        
                        // Update session with current data
                        foreach ($sessionData as $key => $value) {
                            if (!session()->has($key)) {
                                session([$key => $value]);
                            }
                        }
                        
                        Log::info('ForcePortalSession restored user', [
                            'user_id' => $userId,
                            'email' => $user->email,
                            'session_id' => $sessionId,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('ForcePortalSession failed', [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId,
                ]);
            }
        }
        
        return $next($request);
    }
}