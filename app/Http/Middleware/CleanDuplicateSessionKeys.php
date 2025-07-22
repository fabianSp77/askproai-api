<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;

/**
 * Clean Duplicate Session Keys Middleware
 * 
 * PROBLEM: Laravel's session migration during login creates duplicate auth keys:
 * - login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d (Standard Laravel)
 * - login_web_f091f34ca659bece7fff5e7c0e9971e22d1ee510 (Our CustomSessionGuard)
 * 
 * This middleware ensures only the correct key exists in the session.
 */
class CleanDuplicateSessionKeys
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only clean if user is authenticated
        if (Auth::check()) {
            $this->cleanSessionKeys();
        }
        
        return $response;
    }
    
    protected function cleanSessionKeys()
    {
        $session = app('session.store');
        $sessionId = $session->getId();
        
        if (!$sessionId) {
            return;
        }
        
        $sessionFile = storage_path('framework/sessions') . '/' . $sessionId;
        
        if (!file_exists($sessionFile)) {
            return;
        }
        
        // Read session data
        $fileContent = file_get_contents($sessionFile);
        $sessionData = @unserialize($fileContent);
        
        if (!$sessionData || !is_array($sessionData)) {
            return;
        }
        
        // Find all login_web_ keys
        $loginKeys = array_filter(array_keys($sessionData), function($key) {
            return strpos($key, 'login_web_') === 0;
        });
        
        // If more than one key exists, clean it up
        if (count($loginKeys) > 1) {
            // Get the correct key from the guard
            $guard = Auth::guard('web');
            $reflection = new ReflectionMethod($guard, 'getName');
            $reflection->setAccessible(true);
            $correctKey = $reflection->invoke($guard);
            
            // Keep only the correct key
            $userId = null;
            foreach ($loginKeys as $key) {
                if ($key === $correctKey && isset($sessionData[$key])) {
                    $userId = $sessionData[$key];
                } else {
                    unset($sessionData[$key]);
                }
            }
            
            // Write cleaned data back
            $cleanedContent = serialize($sessionData);
            file_put_contents($sessionFile, $cleanedContent);
            
            // Log the cleanup
            \Log::info('CleanDuplicateSessionKeys: Removed duplicate session keys', [
                'session_id' => $sessionId,
                'removed_keys' => array_diff($loginKeys, [$correctKey]),
                'kept_key' => $correctKey,
                'user_id' => $userId
            ]);
        }
    }
}