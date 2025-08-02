<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminPortalSession
{
    /**
     * Configure session for admin portal
     * This runs BEFORE StartSession middleware
     */
    public function handle(Request $request, Closure $next)
    {
        // Only for admin routes
        if ($request->is('admin/*') || $request->is('admin-api/*')) {
            \Log::info('AdminPortalSession: Configuring session for admin', [
                'url' => $request->url(),
                'before_cookie' => config('session.cookie'),
            ]);
            
            // Configure session specifically for admin
            config([
                'session.cookie' => 'askproai_admin_session',
                'session.path' => '/',
                'session.domain' => null, // Let Laravel determine
                'session.secure' => $request->secure(),
                'session.http_only' => true,
                'session.same_site' => 'lax',
                'session.files' => storage_path('framework/sessions/admin'),
                'session.lifetime' => 720, // 12 hours for admin
                'session.encrypt' => false,
            ]);
            
            \Log::info('AdminPortalSession: Session configured', [
                'after_cookie' => config('session.cookie'),
                'after_files' => config('session.files'),
            ]);
        }
        
        return $next($request);
    }
}