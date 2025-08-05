<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SessionManager
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Clean up orphaned sessions
        $this->cleanupOrphanedSessions();
        
        // Validate current session
        $this->validateSession($request);
        
        $response = $next($request);
        
        // Ensure proper session cookie settings
        // Check if response is a standard HTTP response with headers property
        if ($response instanceof \Illuminate\Http\Response || 
            (is_object($response) && method_exists($response, 'headers'))) {
            $this->ensureSessionCookieSettings($response);
        }
        
        return $response;
    }
    
    /**
     * Clean up orphaned or duplicate sessions
     */
    private function cleanupOrphanedSessions()
    {
        try {
            // Delete sessions older than configured lifetime
            $lifetime = config('session.lifetime', 120);
            DB::table('sessions')
                ->where('last_activity', '<', now()->subMinutes($lifetime)->timestamp)
                ->delete();
                
            // If user is authenticated, clean up other sessions for the same user
            if (auth()->check()) {
                $currentSessionId = session()->getId();
                $userId = auth()->id();
                
                // Keep only current session for this user
                DB::table('sessions')
                    ->where('user_id', $userId)
                    ->where('id', '!=', $currentSessionId)
                    ->delete();
            }
        } catch (\Exception $e) {
            Log::error('Session cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate current session integrity
     */
    private function validateSession(Request $request)
    {
        $sessionId = session()->getId();
        
        // Check if session exists in database
        if (config('session.driver') === 'database') {
            $sessionExists = DB::table('sessions')
                ->where('id', $sessionId)
                ->exists();
                
            if (!$sessionExists && !$request->is('*/login')) {
                // Force new session if current doesn't exist
                session()->invalidate();
                session()->regenerateToken();
            }
        }
        
        // Detect and remove duplicate session cookies
        $cookies = $request->cookies->all();
        $sessionCookieName = config('session.cookie');
        $duplicateFound = false;
        
        foreach ($cookies as $name => $value) {
            if ($name !== $sessionCookieName && 
                (str_contains($name, 'session') || strlen($name) > 30)) {
                $duplicateFound = true;
                // Remove duplicate session cookies
                setcookie($name, '', time() - 3600, '/', '', true, true);
            }
        }
        
        if ($duplicateFound) {
            Log::warning('Duplicate session cookies detected and removed', [
                'ip' => $request->ip(),
                'user' => auth()->user()?->email,
            ]);
        }
    }
    
    /**
     * Ensure proper session cookie settings
     */
    private function ensureSessionCookieSettings($response)
    {
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === config('session.cookie')) {
                // Ensure secure flag matches configuration
                if ($cookie->isSecure() !== config('session.secure')) {
                    Log::warning('Session cookie security mismatch detected');
                }
            }
        }
    }
}