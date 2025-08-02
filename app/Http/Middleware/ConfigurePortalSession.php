<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConfigurePortalSession
{
    /**
     * Configure session for portal BEFORE session starts
     * This must run before StartSession middleware
     */
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if ($request->is('business/*') || $request->is('business-api/*')) {
            // Log that we're configuring the session
            \Log::info('ConfigurePortalSession: Configuring session for portal', [
                'url' => $request->url(),
                'path' => $request->path(),
                'before_cookie' => config('session.cookie'),
            ]);
            
            // Configure session for portal - MUST be different from admin
            // For portal, always use null to let Laravel determine domain
            // This prevents issues with different environments
            $domain = null;
            
            config([
                'session.cookie' => 'askproai_portal_session',
                'session.path' => '/',
                'session.domain' => $domain, // null = current domain
                'session.secure' => false, // Disable secure for now to fix login issues
                'session.http_only' => true,
                'session.same_site' => 'lax',
                'session.files' => storage_path('framework/sessions/portal'),
                'session.lifetime' => 480, // 8 hours
                'session.encrypt' => false, // Match EncryptCookies middleware
            ]);
            
            \Log::info('ConfigurePortalSession: Session configured', [
                'after_cookie' => config('session.cookie'),
                'after_domain' => config('session.domain'),
                'after_files' => config('session.files'),
            ]);
        }
        
        return $next($request);
    }
}