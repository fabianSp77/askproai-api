<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocsAuthenticated
{
    /**
     * Handle an incoming request for docs authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated in docs session
        if (!$request->session()->has('docs_authenticated')) {
            // Not authenticated - redirect to login
            return redirect()->route('docs.backup-system.login')
                ->with('intended', $request->fullUrl());
        }

        // Check session timeout (30 minutes)
        $lastActivity = $request->session()->get('docs_last_activity');
        if ($lastActivity && (time() - $lastActivity) > 1800) {
            // Session expired
            $request->session()->forget(['docs_authenticated', 'docs_username', 'docs_last_activity']);
            return redirect()->route('docs.backup-system.login')
                ->with('error', 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.')
                ->with('intended', $request->fullUrl());
        }

        // Update last activity
        $request->session()->put('docs_last_activity', time());

        return $next($request);
    }
}
