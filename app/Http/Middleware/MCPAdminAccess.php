<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MCPAdminAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Check if user has MCP admin permissions
        $hasAccess = $user->hasRole(['Super Admin', 'super_admin', 'developer']) ||
                    $user->email === 'dev@askproai.de' ||
                    $user->can('manage_mcp_configuration');
        
        if (!$hasAccess) {
            return response()->json(['error' => 'Insufficient permissions for MCP administration'], 403);
        }
        
        return $next($request);
    }
}