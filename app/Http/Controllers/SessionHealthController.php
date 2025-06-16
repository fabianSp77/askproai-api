<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SessionHealthController extends Controller
{
    /**
     * Check session health
     */
    public function check(Request $request)
    {
        $health = [
            'status' => 'healthy',
            'session_id' => session()->getId(),
            'authenticated' => auth()->check(),
            'user' => auth()->user()?->email,
            'driver' => config('session.driver'),
            'issues' => [],
        ];
        
        // Check for duplicate sessions
        if (auth()->check()) {
            $duplicateSessions = DB::table('sessions')
                ->where('user_id', auth()->id())
                ->count();
                
            if ($duplicateSessions > 1) {
                $health['issues'][] = 'Multiple sessions detected for user';
                $health['status'] = 'warning';
            }
        }
        
        // Check session age
        $sessionData = DB::table('sessions')
            ->where('id', session()->getId())
            ->first();
            
        if ($sessionData) {
            $age = now()->timestamp - $sessionData->last_activity;
            $health['session_age_minutes'] = round($age / 60, 2);
            
            if ($age > 3600) { // 1 hour
                $health['issues'][] = 'Session is older than 1 hour';
                $health['status'] = 'warning';
            }
        } else {
            $health['issues'][] = 'Session not found in database';
            $health['status'] = 'error';
        }
        
        // Check cookie configuration
        $cookieIssues = $this->checkCookieConfiguration();
        if (!empty($cookieIssues)) {
            $health['issues'] = array_merge($health['issues'], $cookieIssues);
            $health['status'] = 'warning';
        }
        
        return response()->json($health);
    }
    
    /**
     * Force session refresh
     */
    public function refresh(Request $request)
    {
        // Store current auth state
        $user = auth()->user();
        
        if ($user) {
            // Regenerate session
            session()->invalidate();
            session()->regenerateToken();
            
            // Re-authenticate
            auth()->login($user);
            
            return response()->json([
                'success' => true,
                'new_session_id' => session()->getId(),
                'message' => 'Session refreshed successfully',
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No authenticated user',
        ], 401);
    }
    
    /**
     * Check cookie configuration issues
     */
    private function checkCookieConfiguration(): array
    {
        $issues = [];
        
        // Check secure cookie setting vs HTTPS
        if (config('session.secure') && !request()->secure()) {
            $issues[] = 'Secure cookies enabled but not using HTTPS';
        }
        
        // Check domain configuration
        $domain = config('session.domain');
        if (!empty($domain) && !str_contains(request()->getHost(), $domain)) {
            $issues[] = 'Session domain mismatch';
        }
        
        return $issues;
    }
}